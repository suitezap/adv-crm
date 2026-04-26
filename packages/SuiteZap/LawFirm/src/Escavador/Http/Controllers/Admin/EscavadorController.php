<?php

namespace SuiteZap\LawFirm\Escavador\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\Escavador\Services\EscavadorService;
use SuiteZap\LawFirm\Escavador\Services\EscavadorCacheService;
use SuiteZap\LawFirm\Escavador\Models\EscavadorProcesso;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * EscavadorController — Admin controller para integração Escavador.
 *
 * Segue o pattern "Skinny Controller" delegando toda lógica ao EscavadorService / EscavadorCacheService.
 */
class EscavadorController extends Controller
{
    protected $escavador;
    protected $cacheService;

    public function __construct(EscavadorService $escavador, EscavadorCacheService $cacheService)
    {
        $this->escavador = $escavador;
        $this->cacheService = $cacheService;
    }

    /**
     * Dashboard principal com cards de funcionalidades.
     */
    public function index()
    {
        $prices = MotherShipService::getEscavadorPrices();
        return view('lawfirm::admin.escavador.index', compact('prices'));
    }

    /**
     * Mostra a view de gerenciamento de certificados na área de configurações.
     */
    public function viewCertificados()
    {
        return view('lawfirm::admin.escavador.certificados');
    }

    /**
     * Consulta saldo da API (AJAX).
     */
    public function saldo(Request $request)
    {
        $result = $this->escavador->consultarSaldo('v1', false);

        if (!$result['success']) {
            return response()->json($result);
        }

        $data = $result['data'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [$data],
            'credits_used' => $result['credits_used'],
            'status_code' => $result['status_code']
        ]);
    }

    /**
     * Consulta processo por numeração CNJ (V2).
     */
    public function consultarProcesso(Request $request)
    {
        $request->validate([
            'numero_cnj' => 'required|string|min:20',
        ]);

        $playground = (bool) $request->input('playground', false);
        $result = $this->escavador->consultarProcessoCnj(
            $request->input('numero_cnj'),
            $playground
        );

        return response()->json($result);
    }

    /**
     * Consulta movimentações de um processo (V2).
     */
    public function consultarMovimentacoes(Request $request)
    {
        $request->validate([
            'numero_cnj' => 'required|string|min:20',
        ]);

        $playground = (bool) $request->input('playground', false);
        $filters = $request->only(['page', 'items_per_page']);

        $result = $this->escavador->consultarMovimentacoes(
            $request->input('numero_cnj'),
            $filters,
            $playground
        );

        return response()->json($result);
    }

    /**
     * Consulta processos por envolvido — nome ou CPF/CNPJ (V2).
     */
    public function consultarEnvolvido(Request $request)
    {
        $request->validate([
            'nome' => 'required_without:cpf_cnpj|string|nullable',
            'cpf_cnpj' => 'required_without:nome|string|nullable',
        ]);

        $playground = (bool) $request->input('playground', false);
        $params = array_filter($request->only(['nome', 'cpf_cnpj', 'tribunais', 'page']));

        $result = $this->escavador->consultarEnvolvido($params, $playground);

        return response()->json($result);
    }

    /**
     * Consulta processos por OAB (V2).
     */
    public function consultarOab(Request $request)
    {
        $request->validate([
            'numero' => 'required|string',
            'estado' => 'required|string|size:2',
        ]);

        $playground = (bool) $request->input('playground', false);
        $params = $request->only(['numero', 'estado', 'page']);

        $result = $this->escavador->consultarOab($params, $playground);

        return response()->json($result);
    }

    /**
     * Solicitar/consultar resumo inteligente via IA (V2).
     */
    public function resumoIa(Request $request)
    {
        $request->validate([
            'numero_cnj' => 'required|string|min:20',
            'action' => 'required|in:solicitar,consultar,status',
        ]);

        $playground = (bool) $request->input('playground', false);
        $numeroCnj = $request->input('numero_cnj');
        $action = $request->input('action');

        $result = match ($action) {
            'solicitar' => $this->escavador->solicitarResumoIa($numeroCnj, $playground),
            'consultar' => $this->escavador->consultarResumoIa($numeroCnj, $playground),
            default => ['success' => false, 'error' => 'Ação inválida.'],
        };

        return response()->json($result);
    }

    /**
     * Busca genérica por termo (V1).
     *
     * Endpoint: GET api/v1/busca
     * Params obrigatórios: q, qo
     * Params opcionais: qs, limit, page, utilizar_operadores_logicos
     */
    public function buscarTermo(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'qo' => 'required|string|in:t,p,i,d,en',
        ]);

        $playground = (bool) $request->input('playground', false);

        $params = array_filter(
            $request->only(['q', 'qo', 'qs', 'limit', 'page', 'utilizar_operadores_logicos']),
            fn($v) => $v !== null && $v !== ''
        );

        // Defaults conforme a spec da API
        $params['limit'] = $params['limit'] ?? 20;

        $result = $this->escavador->buscarPorTermo($params, $playground);

        return response()->json($result);
    }

    /**
     * Listar monitoramentos ativos (V1).
     */
    public function monitoramentos(Request $request)
    {
        $playground = (bool) $request->input('playground', false);
        $type = $request->input('type', 'diarios'); // diarios | tribunais

        $result = $type === 'tribunais'
            ? $this->escavador->listarMonitoramentosTribunal([], $playground)
            : $this->escavador->listarMonitoramentosDO([], $playground);

        return response()->json($result);
    }

    /**
     * Consulta documentos públicos de um processo (V2).
     */
    public function documentosPublicos(Request $request)
    {
        $request->validate([
            'numero_cnj' => 'required|string|min:20',
        ]);

        $playground = (bool) $request->input('playground', false);

        $result = $this->escavador->consultarDocumentosPublicos(
            $request->input('numero_cnj'),
            $request->only(['page']),
            $playground
        );

        return response()->json($result);
    }

    // ========================================================================
    // SERVIÇOS PAGOS — Débito de Saldo & Cards de Serviço
    // ========================================================================

    /**
     * Executa um serviço pago, debitando o saldo e registrando a requisição.
     *
     * Parâmetros esperados:
     *   - service_type: CAPA_PROCESSO | PDF_DIARIO | BUSCA_TERMO | RESUMO_IA
     *   - data: array de parâmetros da consulta (ex: cnj, q, id)
     *   - processo_id: (opcional) FK para o processo jurídico local
     */
    public function executarServico(Request $request)
    {
        $request->validate([
            'service_type' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::in(array_keys(EscavadorService::SERVICE_MAP))
            ],
            'data' => 'sometimes|array',
            'processo_id' => 'sometimes|nullable|integer',
        ]);

        $tenantId = MotherShipService::getTenantId();
        $serviceType = strtoupper($request->input('service_type'));
        $data = $request->input('data', []);
        $processoId = $request->input('processo_id');

        $result = $this->escavador->requestService(
            $serviceType,
            $data,
            $tenantId,
            $processoId ? (int) $processoId : null
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'async' => $result['async'] ?? false,
            'request_id' => $result['request']?->id,
            'external_id' => $result['request']?->external_id,
            'data' => $result['data'],
            'message' => $result['async']
                ? 'Requisição enviada. O resultado chegará via webhook em instantes.'
                : 'Consulta realizada com sucesso.',
        ]);
    }

    /**
     * Retorna o saldo atual do tenant (para exibição no modal de confirmação).
     */
    public function saldoCliente()
    {
        $tenantId = MotherShipService::getTenantId();
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        return response()->json([
            'success' => (bool) $subscription,
            'ai_tokens_balance' => $subscription ? (float) $subscription->ai_tokens_balance : 0.0,
            'tenant_id' => $tenantId,
        ]);
    }

    // ========================================================================
    // CERTIFICADOS DIGITAIS (V2) — sem débito de créditos SaaS
    // ========================================================================

    /**
     * Lista todos os certificados digitais cadastrados (V2).
     */
    public function listarCertificados(Request $request)
    {
        $playground = (bool) $request->input('playground', false);
        $result = $this->escavador->listarCertificados($playground);
        return response()->json($result);
    }

    /**
     * Cadastra um novo certificado digital via upload de arquivo .pfx (V2).
     *
     * Aceita:
     *   - file : arquivo .pfx / .p12 (multipart)
     *   - senha: senha do certificado
     */
    public function cadastrarCertificado(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pfx,p12|max:4096',
            'senha' => 'required|string|min:1',
        ]);

        $file = $request->file('file');
        $senha = $request->input('senha');
        $playground = (bool) $request->input('playground', false);

        $result = $this->escavador->cadastrarCertificadoComArquivo($file, $senha, $playground);

        // Destaca o ID para exibição amigável no front-end
        if ($result['success'] && isset($result['data']['id'])) {
            $result['cert_id'] = $result['data']['id'];
        }

        return response()->json($result);
    }

    /**
     * Retorna detalhes de um certificado digital pelo ID (V2).
     */
    public function retornarCertificado(Request $request, int $id)
    {
        $playground = (bool) $request->input('playground', false);
        $result = $this->escavador->retornarCertificado($id, $playground);
        return response()->json($result);
    }

    /**
     * Remove um certificado digital pelo ID (V2).
     */
    public function removerCertificado(Request $request, int $id)
    {
        $tenantId = MotherShipService::getTenantId();
        $response = $this->escavador->requestService('DELETE_CERTIFICADO', ['id' => $id], $tenantId);
        return response()->json($response);
    }

    // ─── Novos métodos (Hierarquia de Cache / Espelho local) ─────────────────

    /**
     * Sincroniza a capa de um processo.
     * Consulta no cache local; se não existir, chama V2 Capa e salva.
     */
    public function syncProcesso(Request $request)
    {
        $request->validate([
            'cnj' => 'required|string',
            'processo_id' => 'nullable|integer|exists:processos,id',
        ]);

        $tenantId = MotherShipService::getTenantId();
        $cnj = $request->cnj;
        $processoId = $request->processo_id;

        $escavadorProcesso = $this->cacheService->findOrFetchCapa($cnj, $tenantId, $processoId);

        if (!$escavadorProcesso) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum processo encontrado no Escavador para este número CNJ.',
            ], 404);
        }

        // Tenta também sincronizar as movimentações e envolvidos e documentos assincronamente (se recém criado e nunca tiver feito)
        // Isso pode ser aprimorado com Filas (Jobs), mas manteremos síncrono para MVP do refactoring
        if ($escavadorProcesso->wasRecentlyCreated) {
            $this->cacheService->syncMovimentacoes($escavadorProcesso);
            $this->cacheService->syncEnvolvidos($escavadorProcesso);
            $this->cacheService->syncDocumentosPublicos($escavadorProcesso);

            // Dispara pedido de Resumo IA assíncrono para popular depois
            $this->cacheService->requestResumoIa($escavadorProcesso);
        }

        return response()->json([
            'success' => true,
            'message' => 'Processo sincronizado com sucesso.',
            'data' => $escavadorProcesso->toArray(),
        ]);
    }

    /**
     * Retorna os detalhes cacheados localmente do Escavador.
     */
    public function getProcessoDetails($processoId)
    {
        $tenantId = MotherShipService::getTenantId();

        $escavadorProcesso = EscavadorProcesso::with([
            'movimentacoes',
            'documentos',
            'envolvidos'
        ])
            ->where('tenant_id', $tenantId)
            ->where('processo_id', $processoId)
            ->first();

        if (!$escavadorProcesso) {
            return response()->json(['success' => false, 'message' => 'Processo não sincronizado com o Escavador ainda.'], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'processo' => $escavadorProcesso,
                'is_atualizado' => $escavadorProcesso->isAtualizado(),
                'needs_refresh' => $escavadorProcesso->needsRefresh(),
                'resumo_ia_short' => $escavadorProcesso->getResumoExcerpt()
            ]
        ]);
    }

    /**
     * Solicita a atualização assíncrona no Tribunal (V2)
     */
    public function requestAtualizacao(Request $request)
    {
        $request->validate(['escavador_processo_id' => 'required|integer|exists:escavador_processos,id']);

        $ep = EscavadorProcesso::where('tenant_id', MotherShipService::getTenantId())
            ->findOrFail($request->escavador_processo_id);

        $result = $this->cacheService->requestAtualizacaoTribunal($ep);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Faz download dos autos completos (Ação gata por aprovação de R$ 1,50)
     */
    public function downloadAutos(Request $request)
    {
        $request->validate([
            'numero_cnj' => 'required|string|min:20',
            'processo_id' => 'sometimes|nullable|integer',
        ]);

        $tenantId = MotherShipService::getTenantId();
        $numeroCnj = $request->input('numero_cnj');
        $processoId = $request->input('processo_id');

        $result = $this->escavador->requestService(
            'ATUALIZACAO_PROCESSO_AUTOS',
            ['numero' => $numeroCnj],
            $tenantId,
            $processoId ? (int) $processoId : null
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de download dos autos enviada. O resultado chegará via webhook.',
            'request_id' => $result['request']?->id,
        ]);
    }
}
