<?php

namespace SuiteZap\LawFirm\DataJud\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Models\SaasTransaction;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;

class DataJudService
{
    /**
     * Realiza uma consulta pública na API do DataJud (CNJ).
     *
     * @param  string  $tribunal  Alias do tribunal (ex: api_publica_tjsp)
     * @param  string  $tipoConsulta  'numero' | 'classe_orgao' | 'paginacao'
     * @param  array  $parametros  Parâmetros da busca
     * @param  string  $tenantId  ID do Tenant atual
     */
    public function consultar(string $tribunal, string $tipoConsulta, array $parametros, string $tenantId): array
    {
        $config = MotherShipService::getDataJudConfig();
        $apiKey = $config['api_key'] ?? null;
        $baseUrl = 'https://api-publica.datajud.cnj.jus.br';

        if (! $apiKey) {
            return ['success' => false, 'error' => 'API Key do DataJud não configurada.'];
        }

        $prices = MotherShipService::getEscavadorPrices();
        $costBrlRaw = $prices['DATAJUD_CONSULTA_PUBLICA'] ?? 0.00;
        $costBrlWithMarkup = $costBrlRaw > 0 ? SuiteCoinService::calculateServicePriceBrl($costBrlRaw) : 0.00;

        $subscription = Subscription::where('tenant_id', $tenantId)->first();
        if (! $subscription) {
            return ['success' => false, 'error' => 'Assinatura não encontrada para este tenant.'];
        }

        if ($costBrlWithMarkup > 0) {
            if (! SuiteCoinService::hasSufficientBalance((float) $subscription->suitecoin_balance, $costBrlWithMarkup)) {
                return [
                    'success' => false,
                    'error'   => SuiteCoinService::insufficientBalanceMessage(
                        $subscription->suitecoin_balance,
                        $costBrlWithMarkup
                    ),
                ];
            }

            // Debit upfront
            $subscription->decrement('suitecoin_balance', $costBrlWithMarkup);
        }

        // Build the Elasticsearch query according to the consultation type
        $query = $this->buildQuery($tipoConsulta, $parametros);

        // The URL pattern is /{tribunal}/_search
        $endpointUrl = rtrim($baseUrl, '/').'/'.ltrim($tribunal, '/').'/_search';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'APIKey '.$apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($endpointUrl, $query);

            if ($response->successful()) {
                $data = $response->json();

                if ($costBrlWithMarkup > 0) {
                    SaasTransaction::create([
                        'tenant_id'      => $tenantId,
                        'type'           => 'debit',
                        'amount'         => $costBrlWithMarkup,
                        'balance_after'  => $subscription->suitecoin_balance,
                        'currency'       => SuiteCoinService::CURRENCY_CODE,
                        'service_type'   => 'DATAJUD_CNJ',
                        'description'    => "DataJud ({$tribunal}) - {$tipoConsulta} — ".SuiteCoinService::format(SuiteCoinService::calculateServicePriceVirtual($costBrlRaw)),
                        'user_id'        => auth()->id(),
                        'reference_id'   => $subscription->id,
                        'reference_type' => 'datajud',
                    ]);
                }

                return ['success' => true, 'data' => $data];
            }

            // Estorno em caso de falha
            if ($costBrlWithMarkup > 0) {
                $subscription->increment('suitecoin_balance', $costBrlWithMarkup);
            }
            Log::warning('DataJud API fail', [
                'tribunal' => $tribunal,
                'tipo'     => $tipoConsulta,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            $errorMsg = $response->json('error.reason') ?? 'Erro retornado pela API do DataJud.';

            return ['success' => false, 'error' => $errorMsg];

        } catch (\Exception $e) {
            if ($costBrlWithMarkup > 0) {
                $subscription->increment('suitecoin_balance', $costBrlWithMarkup);
            }
            Log::error('DataJud API Exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Erro de conexão com o DataJud: '.$e->getMessage()];
        }
    }

    /**
     * Monta o corpo da query Elasticsearch de acordo com o tipo de consulta.
     */
    private function buildQuery(string $tipo, array $params): array
    {
        switch ($tipo) {
            // (1) Busca por Número do Processo
            case 'numero':
                $numero = preg_replace('/[^0-9]/', '', $params['numero_cnj'] ?? '');

                return [
                    'query' => [
                        'match' => ['numeroProcesso' => $numero],
                    ],
                ];

                // (2) Busca por Classe e Órgão Julgador
            case 'classe_orgao':
                $must = [];
                if (! empty($params['codigo_classe'])) {
                    $must[] = ['match' => ['classe.codigo' => (int) $params['codigo_classe']]];
                }
                if (! empty($params['codigo_orgao'])) {
                    $must[] = ['match' => ['orgaoJulgador.codigo' => (int) $params['codigo_orgao']]];
                }

                return [
                    'query' => [
                        'bool' => ['must' => $must],
                    ],
                    'sort' => [
                        ['@timestamp' => ['order' => 'desc']],
                    ],
                ];

                // (3) Paginação com search_after
            case 'paginacao':
                $q = ['query' => ['match_all' => (object) []]];
                if (! empty($params['search_after'])) {
                    $q['search_after'] = is_array($params['search_after'])
                        ? $params['search_after']
                        : [$params['search_after']];
                }
                $q['sort'] = [['@timestamp' => ['order' => 'asc']]];
                $q['size'] = (int) ($params['size'] ?? 10);

                return $q;

            default:
                return ['query' => ['match_all' => (object) []]];
        }
    }
}
