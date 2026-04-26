<?php

namespace SuiteZap\LawFirm\Escavador\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\Escavador\Models\EscavadorRequest;

/**
 * EscavadorService — Client para a API do Escavador (V1 & V2).
 *
 * Os tokens de acesso são armazenados na tabela `infrastructure_nodes` (Mothership DB).
 * Nomes esperados:
 *   - Produção: "LawFirm V1", "LawFirm V2"
 *   - Playground: "LawFirm PG V1", "LawFirm PG V2"
 */
class EscavadorService
{
    private const BASE_URL_V1 = 'https://api.escavador.com/api/v1';
    private const BASE_URL_V2 = 'https://api.escavador.com/api/v2';

    /**
     * Mapeamento dos serviços da API do Escavador com as respectivas URLs.
     */
    public const SERVICE_MAP = [
        // Existentes
        'CAPA_PROCESSO' => ['get', 'processos/numero_cnj/{cnj}', 'v2', false],
        'PDF_DIARIO' => ['get', 'monitoramentos-de-diarios/{id}/publicacao/pdf', 'v1', false],
        'BUSCA_TERMO' => ['get', 'busca', 'v1', false],
        'RESUMO_IA' => ['post', 'processos/numero_cnj/{cnj}/ia/resumo/solicitar-atualizacao', 'v2', true],

        // MAPEAMENTO DA UI / ASSISTENTE JURIDICO (V1)
        'API_V1_BUSCARPORTERMO' => ['get', 'busca', 'v1', false],
        'API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL' => ['get', 'diarios/{id}/pdf/pagina/{pagina}/baixar', 'v1', false],
        'API_V1_OBTERPESSOA' => ['get', 'pessoas/{pessoaId}', 'v1', false],
        'API_V1_PROCESSOSDEUMAPESSOA' => ['get', 'pessoas/{pessoaId}/processos', 'v1', false],
        'INFO_INSTITUICAO' => ['get', 'instituicoes/{instituicaoId}', 'v1', false],
        'PROCESSOS_INSTITUICAO' => ['get', 'instituicoes/{instituicaoId}/processos', 'v1', false],
        'PESSOAS_INSTITUICAO' => ['get', 'instituicoes/{instituicaoId}/pessoas', 'v1', false],
        'API_V1_MOVIMENTAESDEUMPROCESSODO' => ['get', 'processos/{processoId}/movimentacoes', 'v1', false],
        'API_V1_BUSCARPROCESSOSDOSDIRIOSPOROAB' => ['get', 'oab/{estado}/{numero}/processos', 'v1', false],
        'BUSCA_PROC_DIARIO_NUM' => ['get', 'processos/numero/{numero}', 'v1', false],
        'API_V1_ENVOLVIDOSDEUMPROCESSO' => ['get', 'processos/{processoId}/envolvidos', 'v1', false],
        'API_V1_PESQUISARPROCESSONOTRIBUNAL' => ['post', 'processo-tribunal/{numero}/async', 'v1', true],
        'API_V1_PESQUISARPROCESSOSPORNOME' => ['post', 'tribunal/{origem}/busca-por-nome/async', 'v1', true],
        'API_V1_PESQUISARPROCESSOSPORCPFOUCNPJ' => ['post', 'tribunal/{origem}/busca-por-documento/async', 'v1', true],
        'API_V1_PESQUISARPROCESSOSPOROAB' => ['post', 'tribunal/{origem}/busca-por-oab/async', 'v1', true],
        'API_V1_PESQUISARPROCESSOADMINISTRATIVONUP' => ['post', 'processo-administrativo/{numero_nup}/async', 'v1', true],
        'API_V1_RETORNARUMAMOVIMENTAO' => ['get', 'movimentacoes/{movimentaco}', 'v1', false],
        'TRIBUNAIS_SISTEMAS' => ['get', 'tribunal/origens', 'v1', false],
        'TRIBUNAIS_DETALHES' => ['get', 'tribunal/origens/{origem}', 'v1', false],
        'ORGAOS_ADMINISTRATIVOS' => ['get', 'orgao-administrativo/origens', 'v1', false],

        // MAPEAMENTO DA UI / ASSISTENTE JURIDICO (V1) — NOVOS
        'API_V1_CONSULTAR_SALDO'         => ['get',    'quantidade-creditos',                              'v1', false],
        'API_V1_TODOS_ASYNC_RESULTADOS'  => ['get',    'async/resultados',                                'v1', false],
        'API_V1_RESULTADO_ASYNC_ID'      => ['get',    'async/resultados/{id}',                           'v1', false],
        'API_V1_MARCAR_CALLBACKS'        => ['post',   'callbacks/marcar-recebidos',                      'v1', false],
        'API_V1_RETORNAR_CALLBACKS'      => ['get',    'callbacks',                                       'v1', false],
        'API_V1_REENVIAR_CALLBACK'       => ['post',   'callbacks/{id}/reenviar',                         'v1', false],
        'API_V1_RETORNAR_ORIGENS'        => ['get',    'origens',                                         'v1', false],
        'API_V1_PAGINA_DIARIO'           => ['get',    'diarios/{id}',                                    'v1', false],
        'API_V1_RETORNAR_MONITORAMENTOS' => ['get',    'monitoramentos',                                  'v1', false],
        'API_V1_RETORNAR_MONITORAMENTO'  => ['get',    'monitoramentos/{monitoramento}',                   'v1', false],
        'API_V1_RETORNAR_APARICOES'      => ['get',    'monitoramentos/{monitoramento}/aparicoes',         'v1', false],
        'API_V1_REMOVER_MONITORAMENTO'   => ['delete', 'monitoramentos/{monitoramento}',                   'v1', false],
        'API_V1_CRIAR_MONITORAMENTO'     => ['post',   'monitoramentos',                                   'v1', false],
        'API_V1_TESTAR_CALLBACK'         => ['post',   'monitoramentos/testcallback',                      'v1', false],
        'API_V1_DIARIOS_MONITORADOS'     => ['get',    'monitoramentos/{monitoramento}/origens',            'v1', false],

        // MAPEAMENTO DA UI / ASSISTENTE JURIDICO (V2)
        'API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME' => ['get',  'envolvido/processos',                  'v2', false],
        'API_V2_PROCESSOSDEUMADVOGADOPOROAB'          => ['get',  'advogado/processos',                   'v2', false],
        'API_V2_PROCESSOPORNUMERAOCNJCAPA'            => ['get',  'processos/numero_cnj/{numero}',         'v2', false],
        'API_V2_MOVIMENTAESDEUMPROCESSO'              => ['get',  'processos/numero_cnj/{numero}/movimentacoes', 'v2', false],
        'API_V2_STATUS_ATUALIZACAO'                   => ['get',  'processos/numero_cnj/{numero}/status-atualizacao', 'v2', false],
        'API_V2_SOLICITARATUALIZAODEUMPROCESSO'       => ['post', 'processos/numero_cnj/{numero}/solicitar-atualizacao', 'v2', true],  // bugfix: era GET
        'API_V2_TRIBUNAIS_DISPONIVEIS'                => ['get',  'tribunais',                             'v2', false],

        // MAPEAMENTO DA UI / ASSISTENTE JURIDICO (V2) — NOVOS
        'API_V2_RESUMO_OAB'             => ['get',    'advogado/processos/resumo',                         'v2', false],
        'API_V2_RESUMO_ENVOLVIDO'       => ['get',    'envolvido/processos/resumo',                        'v2', false],
        'API_V2_AUTOS_PROCESSO'         => ['get',    'processos/numero_cnj/{numero}/autos',                'v2', false],
        'API_V2_DOCS_PUBLICOS'          => ['get',    'processos/numero_cnj/{numero}/documentos-publicos',  'v2', false],
        'API_V2_ENVOLVIDOS_PROCESSO'    => ['get',    'processos/numero_cnj/{numero}/envolvidos',           'v2', false],
        'API_V2_RESUMO_IA_PROCESSO'     => ['post',   'processos/numero_cnj/{numero}/ia/resumo/solicitar-atualizacao', 'v2', true],
        'API_V2_STATUS_RESUMO_IA_UI'    => ['get',    'processos/numero_cnj/{numero}/ia/resumo/status',    'v2', false],
        'API_V2_SISTEMAS_DISPONIVEIS'   => ['get',    'tribunais/sistemas',                                'v2', false],
        'API_V2_CALLBACKS_LISTAR'       => ['get',    'callbacks',                                         'v2', false],

        // MAPEAMENTO LEGADO MANTIDO PARA CACHE/TESTES
        'BUSCA_JURIS' => ['get', 'jurisprudencias/busca', 'v1', false],
        'BUSCA_DIARIO' => ['get', 'diarios/busca', 'v1', false],
        'BUSCA_OAB_PAGA' => ['get', 'oab/{estado}/{numero}/processos', 'v1', false],
        'DOC_JURIS' => ['get', 'jurisprudencias/documento/{tipo_documento}/{id_documento}', 'v1', false],
        'PDF_JURIS' => ['get', 'jurisprudencias/documento/{tipo_documento}/{id_documento}/pdf', 'v1', false],
        'BUSCA_LEGIS' => ['get', 'legislacoes/busca', 'v1', false],
        'DOC_LEGIS' => ['get', 'legislacoes/documento/{tipo_documento}/{id_documento}', 'v1', false],
        'FRAG_LEGIS' => ['get', 'legislacoes/documento/{tipo_documento}/{id_documento}/fragmentos', 'v1', false],
        'AUTOS_DOCS_ESP' => ['get', 'processos/{id}/documentos', 'v1', false],
        
        // === Monitoramentos e Async Base (V1 & V2) ===
        'ASYNC_RESULTADOS' => ['get', 'async/resultados', 'v1', false],
        'ASYNC_RESULTADO_ID' => ['get', 'async/resultados/{id}', 'v1', false],
        'CALLBACKS_MARCAR_RECEBIDOS' => ['post', 'callbacks/marcar-recebidos', 'v1', false],
        'CALLBACKS_LISTAR' => ['get', 'callbacks', 'v1', false],
        'CALLBACKS_REENVIAR' => ['post', 'callbacks/{id}/reenviar', 'v1', false],
        'MONITORAMENTOS_LISTAR' => ['get', 'monitoramentos', 'v1', false],
        'MONITORAMENTOS_ID' => ['get', 'monitoramentos/{monitoramento}', 'v1', false],
        'MONITORAMENTOS_EDITAR' => ['put', 'monitoramentos/{monitoramento}', 'v1', false],
        'MONITORAMENTOS_REMOVER' => ['delete', 'monitoramentos/{monitoramento}', 'v1', false],
        'MONITORAMENTOS_APARICOES' => ['get', 'monitoramentos/{monitoramento}/aparicoes', 'v1', false],
        'CRIAR_MON_DIARIOS' => ['post', 'monitoramentos', 'v1', false],
        'CRIAR_MON_TRIBUNAL' => ['post', 'monitoramentos-tribunal', 'v1', false],
        'CRIAR_MON_PROCESSO_V2'      => ['post', 'monitoramentos/processos',      'v2', false],
        'CRIAR_MON_NOVOS_PROCESSO_V2'=> ['post', 'monitoramentos/novos-processos','v2', false],
        
        'STATUS_ATUALIZACAO_PROCESSO' => ['get', 'processos/numero_cnj/{numero}/status-atualizacao', 'v2', false],
        'CALLBACKS_LISTAR_V2' => ['get', 'callbacks', 'v2', false],
        'CALLBACKS_MARCAR_RECEBIDOS_V2' => ['post', 'callbacks/marcar-recebidos', 'v2', false],
        'CALLBACKS_REENVIAR_V2' => ['post', 'callbacks/{id}/reenviar', 'v2', false],
        'MONITORAMENTO_NOVOS_PROCESSO_LISTAR' => ['get', 'monitoramentos/novos-processos', 'v2', false],
        'MONITORAMENTO_NOVOS_PROCESSO_ID' => ['get', 'monitoramentos/novos-processos/{id}', 'v2', false],
        'MONITORAMENTO_NOVOS_PROCESSO_REMOVER' => ['delete', 'monitoramentos/novos-processos/{id}', 'v2', false],
        'MONITORAMENTO_NOVOS_PROCESSO_RESULTADOS' => ['get', 'monitoramentos/novos-processos/{id}/resultados', 'v2', false],
        'MONITORAMENTO_NOVOS_PROCESSO_EDITAR' => ['patch', 'monitoramentos/novos-processos/{id}', 'v2', false],
        'MONITORAMENTO_PROCESSO_LISTAR' => ['get', 'monitoramentos/processos', 'v2', false],
        'MONITORAMENTO_PROCESSO_ID' => ['get', 'monitoramentos/processos/{id}', 'v2', false],
        'MONITORAMENTO_PROCESSO_REMOVER' => ['delete', 'monitoramentos/processos/{id}', 'v2', false],
        'STATUS_RESUMO_IA' => ['get', 'processos/numero_cnj/{numero}/ia/resumo/status', 'v2', false],
    ];

    /**
     * Retorna a API key para a versão solicitada.
     *
     * @param string $version 'v1' ou 'v2'
     * @param bool $playground Se true, usa token de playground (PG)
     * @return string|null
     */
    public function getApiKey(string $version, bool $playground = false): ?string
    {
        // The user explicitly requested to always use 'LawFimr V1 e V2'
        $name = 'LawFimr V1 e V2';

        $cacheKey = 'escavador_api_key_' . md5($name);

        return Cache::remember($cacheKey, 300, function () use ($name) {
            $node = InfrastructureNode::on('mothership')
                ->where('name', $name)
                ->where('status', 'active')
                ->first();

            return $node?->api_key;
        });
    }

    /**
     * Executa uma requisição HTTP para a API do Escavador.
     *
     * @param string $method 'get' ou 'post'
     * @param string $endpoint Endpoint relativo (ex: '/processos/numero_cnj/...')
     * @param array $params Query params (GET) ou body (POST)
     * @param string $version 'v1' ou 'v2'
     * @param bool $playground Usar token de playground
     * @return array ['success' => bool, 'data' => mixed, 'credits_used' => int|null, 'error' => string|null, 'status_code' => int]
     */
    public function request(string $method, string $endpoint, array $params = [], string $version = 'v2', bool $playground = false): array
    {
        $apiKey = $this->getApiKey($version, $playground);

        if (!$apiKey) {
            return [
                'success' => false,
                'data' => null,
                'credits_used' => null,
                'error' => "Token da API Escavador ({$version}) não encontrado na infraestrutura. Verifique a tabela infrastructure_nodes.",
                'status_code' => 0,
            ];
        }

        $baseUrl = $version === 'v1' ? self::BASE_URL_V1 : self::BASE_URL_V2;
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $http = Http::withToken($apiKey)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->timeout(30);

            $methodLower = strtolower($method);
            if ($methodLower === 'get') {
                $response = $http->get($url, $params);
            } elseif ($methodLower === 'post') {
                $response = $http->post($url, $params);
            } elseif ($methodLower === 'put') {
                $response = $http->put($url, $params);
            } elseif ($methodLower === 'patch') {
                $response = $http->patch($url, $params);
            } elseif ($methodLower === 'delete') {
                $response = $http->delete($url, $params);
            } else {
                throw new \InvalidArgumentException("Método HTTP não suportado: {$method}");
            }

            $creditsUsed = $response->header('Creditos-Utilizados');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'credits_used' => $creditsUsed ? (int) $creditsUsed : null,
                    'error' => null,
                    'status_code' => $response->status(),
                ];
            }

            $errorBody = $response->json();
            $errorMsg = $errorBody['error'] ?? $errorBody['message'] ?? 'Erro desconhecido da API Escavador.';

            // Intercept message "Seu saldo está bloqueado. Faça uma recarga..." from the Escavador API
            if (str_contains($errorMsg, 'saldo está bloqueado') || str_contains($errorMsg, 'recarga para voltar a utilizar a API')) {
                $errorMsg = 'Atenção - Problemas com a API, por favor entre em contato com o suporte.';
            }

            Log::warning('Escavador API error', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $errorBody,
                'version' => $version,
            ]);

            return [
                'success' => false,
                'data' => $errorBody,
                'credits_used' => $creditsUsed ? (int) $creditsUsed : null,
                'error' => $errorMsg,
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Escavador API exception', [
                'url' => $url ?? 'N/A',
                'message' => $e->getMessage(),
                'version' => $version,
            ]);

            return [
                'success' => false,
                'data' => null,
                'credits_used' => null,
                'error' => 'Erro de conexão com a API Escavador: ' . $e->getMessage(),
                'status_code' => 0,
            ];
        }
    }

    // ========================================================================
    // API V2 — Consulta de Processos
    // ========================================================================

    /**
     * Consulta processo por numeração CNJ.
     */
    public function consultarProcessoCnj(string $numeroCnj, bool $playground = false): array
    {
        $numero = trim($numeroCnj);
        return $this->request('get', "processos/numero_cnj/{$numero}", [], 'v2', $playground);
    }

    /**
     * Consulta movimentações de um processo.
     */
    public function consultarMovimentacoes(string $numeroCnj, array $filters = [], bool $playground = false): array
    {
        $numero = trim($numeroCnj);
        return $this->request('get', "processos/numero_cnj/{$numero}/movimentacoes", $filters, 'v2', $playground);
    }

    /**
     * Consulta processos de envolvido por nome ou CPF/CNPJ.
     */
    public function consultarEnvolvido(array $params, bool $playground = false): array
    {
        return $this->request('get', 'envolvido/processos', $params, 'v2', $playground);
    }

    /**
     * Consulta processos de advogado por OAB.
     */
    public function consultarOab(array $params, bool $playground = false): array
    {
        return $this->request('get', 'advogado/processos', $params, 'v2', $playground);
    }

    /**
     * Solicita geração/atualização do resumo inteligente de um processo (IA).
     */
    public function solicitarResumoIa(string $numeroCnj, bool $playground = false): array
    {
        $numero = trim($numeroCnj);
        return $this->request('post', "processos/numero_cnj/{$numero}/ia/resumo/solicitar-atualizacao", [], 'v2', $playground);
    }

    /**
     * Consulta resumo inteligente de um processo.
     */
    public function consultarResumoIa(string $numeroCnj, bool $playground = false): array
    {
        $numero = trim($numeroCnj);
        return $this->request('get', "processos/numero_cnj/{$numero}/ia/resumo", [], 'v2', $playground);
    }

    /**
     * Consulta documentos públicos de um processo.
     */
    public function consultarDocumentosPublicos(string $numeroCnj, array $filters = [], bool $playground = false): array
    {
        $numero = trim($numeroCnj);
        return $this->request('get', "processos/numero_cnj/{$numero}/documentos-publicos", $filters, 'v2', $playground);
    }

    /**
     * Consulta envolvidos de um processo.
     */
    public function consultarEnvolvidosProcesso(string $numeroCnj, array $filters = [], bool $playground = false): array
    {
        $numero = trim($numeroCnj);
        return $this->request('get', "processos/numero_cnj/{$numero}/envolvidos", $filters, 'v2', $playground);
    }

    /**
     * Retorna os tribunais disponíveis.
     */
    public function consultarTribunais(bool $playground = false): array
    {
        return $this->request('get', 'tribunais', [], 'v2', $playground);
    }

    // ========================================================================
    // API V1 — Busca, Monitoramentos, Saldo
    // ========================================================================

    /**
     * Busca por termo genérico.
     */
    public function buscarPorTermo(array $params, bool $playground = false): array
    {
        return $this->request('get', 'busca', $params, 'v1', $playground);
    }

    /**
     * Consulta saldo de créditos da API (api/v1/quantidade-creditos).
     */
    public function consultarSaldo(string $version = 'v1', bool $playground = false): array
    {
        return $this->request('get', 'quantidade-creditos', [], $version, $playground);
    }

    /**
     * Lista monitoramentos de Diários Oficiais.
     */
    public function listarMonitoramentosDO(array $params = [], bool $playground = false): array
    {
        return $this->request('get', 'monitoramentos-de-diarios', $params, 'v1', $playground);
    }

    /**
     * Cria monitoramento de Diário Oficial.
     */
    public function criarMonitoramentoDO(array $params, bool $playground = false): array
    {
        return $this->request('post', 'monitoramentos-de-diarios', $params, 'v1', $playground);
    }

    /**
     * Lista monitoramentos de Tribunais.
     */
    public function listarMonitoramentosTribunal(array $params = [], bool $playground = false): array
    {
        return $this->request('get', 'monitoramentos-de-tribunais', $params, 'v1', $playground);
    }

    /**
     * Cria monitoramento de Tribunal.
     */
    public function criarMonitoramentoTribunal(array $params, bool $playground = false): array
    {
        return $this->request('post', 'monitoramentos-de-tribunais', $params, 'v1', $playground);
    }

    // ========================================================================
    // SERVIÇOS PAGOS — Débito de Saldo & Rastreamento
    // ========================================================================

    /**
     * Executa um serviço pago do Escavador com débito de saldo e rastreamento.
     *
     * Fluxo:
     *   1. Valida serviceType e custo.
     *   2. Verifica saldo da Subscription.
     *   3. Debita imediatamente.
     *   4. Chama a API (V1 síncrono ou V2 assíncrono).
     *   5. Persiste EscavadorRequest.
     *   6. Em falha: estorna o saldo e marca como 'failed'.
     *
     * @param string      $serviceType  Um dos tipos em SERVICE_PRICES
     * @param array       $data         Parâmetros para a API (ex: ['cnj' => '...'])
     * @param string      $tenantId     ID do tenant dono da requisição
     * @param int|null    $processoId   FK opcional para law_processes
     * @param bool        $playground   Usar tokens de playground
     * @return array  ['success', 'request' => EscavadorRequest, 'data', 'error']
     */
    public function requestService(
        string $serviceType,
        array $data,
        string $tenantId,
        ?int $processoId = null,
        bool $playground = false
    ): array {
        // ── 1. Validar tipo de serviço e custo dinâmico ────────────────────
        $serviceType = strtoupper($serviceType);
        $prices = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getEscavadorPrices();

        if (!array_key_exists($serviceType, $prices)) {
            return [
                'success' => false,
                'error' => "Tipo de serviço inválido: {$serviceType}.",
                'request' => null,
            ];
        }

        $cost = $prices[$serviceType];

        // ── 2. Verificar saldo da Subscription ────────────────────────────
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        if (!$subscription) {
            return [
                'success' => false,
                'error' => 'Assinatura não encontrada para o tenant.',
                'request' => null,
            ];
        }

        if ((float) $subscription->ai_tokens_balance < $cost) {
            return [
                'success' => false,
                'error' => sprintf(
                    'Saldo insuficiente. Disponível: R$ %.2f | Necessário: R$ %.2f',
                    $subscription->ai_tokens_balance,
                    $cost
                ),
                'request' => null,
            ];
        }

        // ── 2b. Verificar Janela de Cache (24h default) ────────────────────
        $requestHash = md5(json_encode(collect($data)->sortKeys()->toArray()));
        $cacheHours = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getEscavadorCacheWindowHours();

        if ($cacheHours > 0) {
            $cachedRequest = EscavadorRequest::where('tenant_id', $tenantId)
                ->where('endpoint_type', $serviceType)
                ->where('request_hash', $requestHash)
                ->where('status', EscavadorRequest::STATUS_COMPLETED)
                ->where('created_at', '>=', now()->subHours($cacheHours))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($cachedRequest && $cachedRequest->payload_response) {
                return [
                    'success' => true,
                    'request' => $cachedRequest,
                    'data' => $cachedRequest->payload_response,
                    'error' => null,
                    'async' => false,
                    'cached' => true,
                ];
            }
        }

        // ── 3. Debitar imediatamente ───────────────────────────────────────
        $subscription->decrement('ai_tokens_balance', $cost);

        // ── 4. Resolver endpoint e fazer a chamada ─────────────────────────
        [$method, $endpointTemplate, $version, $isAsync] = self::SERVICE_MAP[$serviceType];

        $params = $data;
        
        // Substituir placeholders {chave} na URL e remover os valores do array de parâmetros
        $endpoint = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use (&$params) {
            $key = $matches[1];
            if (isset($params[$key]) && $params[$key] !== '') {
                $val = urlencode(trim((string)$params[$key]));
                unset($params[$key]);
                return $val;
            }
            return ''; // Remove o placeholder se não foi passado (cobre parâmetros opcionais de rota)
        }, $endpointTemplate);

        $endpoint = rtrim(preg_replace('#/+#', '/', $endpoint), '/');

        $apiResult = $this->request($method, $endpoint, $params, $version, $playground);

        // ── 5. Persistir EscavadorRequest ─────────────────────────────────
        $externalId = null;
        $statusRecord = EscavadorRequest::STATUS_COMPLETED;
        $payloadResponse = null;

        if ($apiResult['success']) {
            if ($isAsync) {
                // V2 assíncrono: guarda o ID retornado e aguarda webhook
                $externalId = $apiResult['data']['id'] ?? ($apiResult['data']['request_id'] ?? null);
                $statusRecord = EscavadorRequest::STATUS_PENDING;
            } else {
                // V1 síncrono: resultado já disponível
                $payloadResponse = $apiResult['data'];
            }
        } else {
            // Falha na API: estornar o saldo
            $subscription->increment('ai_tokens_balance', $cost);
            $statusRecord = EscavadorRequest::STATUS_FAILED;

            Log::warning('EscavadorService::requestService — falha na API', [
                'service' => $serviceType,
                'tenant_id' => $tenantId,
                'error' => $apiResult['error'],
            ]);
        }

        $escavadorRequest = EscavadorRequest::create([
            'tenant_id' => $tenantId,
            'processo_id' => $processoId,
            'external_id' => $externalId,
            'request_hash' => $requestHash,
            'endpoint_type' => $serviceType,
            'status' => $statusRecord,
            'cost' => $apiResult['success'] ? $cost : 0.00,
            'payload_response' => $payloadResponse,
        ]);

        if ($apiResult['success']) {
            \SuiteZap\LawFirm\SaaS\Models\SaasTransaction::create([
                'tenant_id' => $tenantId,
                'type' => 'debit',
                'amount' => $cost,
                'balance_after' => $subscription->ai_tokens_balance,
                'service_type' => 'ESCAVADOR_' . $serviceType,
                'description' => "Escavador: {$serviceType} — Custo: R$ {$cost}",
                'user_id' => auth()->id(),
                'reference_id' => $escavadorRequest->id,
                'reference_type' => EscavadorRequest::class,
            ]);
        }

        return [
            'success' => $apiResult['success'],
            'request' => $escavadorRequest,
            'data' => $apiResult['data'],
            'error' => $apiResult['error'] ?? null,
            'async' => $isAsync && $apiResult['success'],
        ];
    }

    // ========================================================================
    // CERTIFICADOS DIGITAIS (V2) — sem débito de créditos SaaS
    // ========================================================================

    /**
     * Lista todos os certificados digitais cadastrados.
     * GET api/v2/certificados-digitais
     */
    public function listarCertificados(bool $playground = false): array
    {
        return $this->request('get', 'certificados-digitais', [], 'v2', $playground);
    }

    /**
     * Cadastra um novo certificado digital via upload multipart (.pfx / .p12).
     * POST api/v2/certificados-digitais
     *
     * @param \Illuminate\Http\UploadedFile $file  Arquivo do certificado (.pfx ou .p12)
     * @param string                         $senha Senha do certificado
     */
    public function cadastrarCertificadoComArquivo(\Illuminate\Http\UploadedFile $file, string $senha, bool $playground = false): array
    {
        $apiKey = $this->getApiKey('v2', $playground);

        if (!$apiKey) {
            return [
                'success'      => false,
                'data'         => null,
                'credits_used' => null,
                'error'        => 'Token da API Escavador (v2) não encontrado na infraestrutura.',
                'status_code'  => 0,
            ];
        }

        $url = rtrim(self::BASE_URL_V2, '/') . '/certificados-digitais';

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($url, ['senha' => $senha]);

            $creditsUsed = $response->header('Creditos-Utilizados');

            if ($response->successful()) {
                return [
                    'success'      => true,
                    'data'         => $response->json(),
                    'credits_used' => $creditsUsed ? (int) $creditsUsed : null,
                    'error'        => null,
                    'status_code'  => $response->status(),
                ];
            }

            $errorBody = $response->json();
            $errorMsg  = $errorBody['error'] ?? $errorBody['message'] ?? 'Erro desconhecido ao cadastrar certificado.';

            \Illuminate\Support\Facades\Log::warning('Escavador certificado upload error', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $errorBody,
            ]);

            return [
                'success'      => false,
                'data'         => $errorBody,
                'credits_used' => null,
                'error'        => $errorMsg,
                'status_code'  => $response->status(),
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Escavador certificado upload exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return [
                'success'      => false,
                'data'         => null,
                'credits_used' => null,
                'error'        => 'Erro de conexão ao enviar certificado: ' . $e->getMessage(),
                'status_code'  => 0,
            ];
        }
    }

    /**
     * Cadastra um novo certificado digital via JSON (compatibilidade legada).
     * POST api/v2/certificados-digitais
     *
     * @param array $params Ex.: ['cpf' => '...', 'senha' => '...']
     */
    public function cadastrarCertificado(array $params, bool $playground = false): array
    {
        return $this->request('post', 'certificados-digitais', $params, 'v2', $playground);
    }

    /**
     * Retorna detalhes de um certificado digital pelo ID.
     * GET api/v2/certificados-digitais/{id}
     */
    public function retornarCertificado(int $id, bool $playground = false): array
    {
        return $this->request('get', "certificados-digitais/{$id}", [], 'v2', $playground);
    }

    /**
     * Remove um certificado digital pelo ID.
     * DELETE api/v2/certificados-digitais/{id}
     */
    public function removerCertificado(int $id, bool $playground = false): array
    {
        return $this->request('delete', "certificados-digitais/{$id}", [], 'v2', $playground);
    }
}
