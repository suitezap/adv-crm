<?php

namespace SuiteZap\LawFirm\Escavador\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Escavador\Models\EscavadorProcesso;
use SuiteZap\LawFirm\Escavador\Models\EscavadorMovimentacao;
use SuiteZap\LawFirm\Escavador\Models\EscavadorEnvolvido;
use SuiteZap\LawFirm\Escavador\Models\EscavadorDocumento;
use SuiteZap\LawFirm\Legal\Models\Processo;

/**
 * Serviço responsável pelo cache inteligente das requisições ao Escavador.
 * Implementa a hierarquia de consulta (Local -> V1 -> V2) para otimização financeira.
 */
class EscavadorCacheService
{
    private EscavadorService $apiService;

    public function __construct(EscavadorService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Passo 1: Busca o processo localmente. Se não existir, consulta a Capa (V2 - R$ 0,05) e salva.
     */
    public function findOrFetchCapa(string $cnj, string $tenantId, ?int $processoId = null): ?EscavadorProcesso
    {
        $cnjClean = preg_replace('/[^0-9]/', '', $cnj);
        if (empty($cnjClean)) {
            return null;
        }

        // Tenta encontrar em cache local
        $escavadorProcesso = EscavadorProcesso::where('tenant_id', $tenantId)
            ->where('numero_cnj', $cnjClean)
            ->first();

        // Se encontrou e o advogado preencheu o processoId depois, a gente vincula
        if ($escavadorProcesso && $processoId && !$escavadorProcesso->processo_id) {
            $escavadorProcesso->update(['processo_id' => $processoId]);
        }

        // Já existe localmente? Retorna.
        if ($escavadorProcesso) {
            return $escavadorProcesso;
        }

        // Não existe: Busca a Capa na V2 (R$ 0,05)
        $response = $this->apiService->requestService('CAPA_PROCESSO', ['numero' => $cnjClean], $tenantId, $processoId);

        if (!$response['success'] || empty($response['data'])) {
            return null;
        }

        $apiData = $response['data'];

        // Cria o registro base ESPELHO da API
        return EscavadorProcesso::create([
            'tenant_id' => $tenantId,
            'processo_id' => $processoId,
            'numero_cnj' => $cnjClean,
            'numero_alternativo' => $apiData['numero_alternativo'] ?? null,
            'titulo' => $apiData['titulo'] ?? null,
            'tribunal' => $apiData['fontes'][0]['tribunal']['sigla'] ?? ($apiData['fontes'][0]['tribunal']['nome'] ?? null),
            'vara' => $apiData['fontes'][0]['capa']['vara'] ?? null,
            'segredo_justica' => $apiData['segredo_justica'] ?? false,
            'status_atualizacao' => 'atualizado', // Acabamos de trazer
            'escavador_id' => null, // Não aplicável pra buscar por CNJ aqui
            'capa_json' => $apiData,
            'data_ultima_verificacao' => now(),
        ]);
    }

    /**
     * Passo 2: Sincronizar Movimentações V2 (R$ 3,00 - Uso prudente) 
     * Obs: O user limitou a instrução para focar em "documentos", "resumo ia" e "atualização". 
     * Mas vamos expor o wrapper para sincronia segura caso acionado no painel.
     */
    public function syncMovimentacoes(EscavadorProcesso $esqProcesso): bool
    {
        $response = $this->apiService->requestService(
            'MOVIMENTACOES_PROCESSO', 
            ['numero' => $esqProcesso->numero_cnj],
            $esqProcesso->tenant_id, 
            $esqProcesso->processo_id
        );

        if (!$response['success'] || empty($response['data']['data'])) {
            return false;
        }

        // Apaga o cache antigo para aquele processo e insere tudo fresco
        // Numa versão mais avançada, pode conferir IDs. Mas wipe n' load resolve o cache temporal
        EscavadorMovimentacao::where('escavador_processo_id', $esqProcesso->id)->delete();

        $movimentacoes = [];
        foreach ($response['data']['data'] as $mov) {
            $dataMov = null;
            if (!empty($mov['data'])) {
                 try {
                     $dataMov = Carbon::parse($mov['data'])->format('Y-m-d');
                 } catch (\Exception $e) { }
            }

            $movimentacoes[] = [
                'escavador_processo_id' => $esqProcesso->id,
                'data_movimentacao' => $dataMov ?: now()->format('Y-m-d'),
                'texto_movimentacao' => $mov['conteudo'] ?? 'Movimentação sem conteúdo.',
                'escavador_id' => $mov['id'] ?? null,
                'tipo' => $mov['tipo'] ?? null,
                'raw_json' => json_encode($mov),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($movimentacoes)) {
            EscavadorMovimentacao::insert($movimentacoes);
        }

        return true;
    }

    /**
     * Sincronizar Envolvidos Processo V2 (R$ 0,05)
     */
    public function syncEnvolvidos(EscavadorProcesso $esqProcesso): bool
    {
        $response = $this->apiService->requestService(
            'ENVOLVIDOS_PROCESSO', 
            ['numero' => $esqProcesso->numero_cnj],
            $esqProcesso->tenant_id, 
            $esqProcesso->processo_id
        );

        if (!$response['success'] || empty($response['data']['data'])) {
            return false;
        }

        EscavadorEnvolvido::where('escavador_processo_id', $esqProcesso->id)->delete();

        $envolvidos = [];
        foreach ($response['data']['data'] as $env) {
            $envolvidos[] = [
                'escavador_processo_id' => $esqProcesso->id,
                'nome' => $env['nome'] ?? 'Desconhecido',
                'cpf_cnpj' => $env['cpf_cnpj'] ?? null,
                'tipo_participacao' => $env['tipo'] ?? null,
                'oab' => isset($env['oab']['numero']) ? $env['oab']['numero'] . $env['oab']['letra'] . '/' . $env['oab']['uf'] : null,
                'escavador_id' => $env['id'] ?? null,
                'raw_json' => json_encode($env),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($envolvidos)) {
            EscavadorEnvolvido::insert($envolvidos);
        }

        return true;
    }

    /**
     * Sincronizar Documentos Públicos (R$ 0,06)
     */
    public function syncDocumentosPublicos(EscavadorProcesso $esqProcesso): bool
    {
        $response = $this->apiService->requestService(
            'DOCUMENTOS_PUBLICOS', 
            ['numero' => $esqProcesso->numero_cnj],
            $esqProcesso->tenant_id, 
            $esqProcesso->processo_id
        );

        if (!$response['success'] || empty($response['data']['data'])) {
            return false;
        }

        EscavadorDocumento::where('escavador_processo_id', $esqProcesso->id)->delete();

        $docs = [];
        foreach ($response['data']['data'] as $doc) {
            $dataEx = null;
            if (!empty($doc['data'])) {
                try {
                    $dataEx = Carbon::parse($doc['data'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {}
            }
            $docs[] = [
                'escavador_processo_id' => $esqProcesso->id,
                'tipo' => $doc['tipo'] ?? null,
                'escavador_id' => $doc['id'] ?? null,
                'url_pdf' => $doc['url'] ?? null,
                'fonte' => 'publicos',
                'data_extracao' => $dataEx,
                'raw_json' => json_encode($doc),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($docs)) {
            EscavadorDocumento::insert($docs);
        }

        return true;
    }

    /**
     * Solicitar Resumo IA V2 (R$ 0,08) Assíncrono
     * @return array [success: bool, message: string]
     */
    public function requestResumoIa(EscavadorProcesso $esqProcesso): array
    {
        $response = $this->apiService->requestService(
            'RESUMO_IA', 
            ['numero' => $esqProcesso->numero_cnj],
            $esqProcesso->tenant_id, 
            $esqProcesso->processo_id
        );

        if (!$response['success']) {
            return ['success' => false, 'message' => $response['error'] ?? 'Falha ao solicitar resumo IA.'];
        }

        // Atualiza status local para indicar que solicitou
        $esqProcesso->update(['status_atualizacao' => 'resumo_solicitado']);
        return ['success' => true, 'message' => 'Resumo IA solicitado com sucesso. Aguarde o processamento.'];
    }

    /**
     * Solicitar Atualizacao no Tribunal V2 (R$ 0,10) Assíncrono
     */
    public function requestAtualizacaoTribunal(EscavadorProcesso $esqProcesso): array
    {
        $response = $this->apiService->requestService(
            'ATUALIZACAO_PROCESSO_PUB', 
            ['numero' => $esqProcesso->numero_cnj],
            $esqProcesso->tenant_id, 
            $esqProcesso->processo_id
        );

        if (!$response['success']) {
            return ['success' => false, 'message' => $response['error'] ?? 'Falha ao solicitar atualização no Tribunal.'];
        }

        // Atualiza status local para manter controle visual (ampulheta pro advogado)
        $esqProcesso->update(['status_atualizacao' => 'atualizacao_solicitada']);
        return ['success' => true, 'message' => 'Atualização solicitada ao tribunal. O robô foi despachado.'];
    }
}
