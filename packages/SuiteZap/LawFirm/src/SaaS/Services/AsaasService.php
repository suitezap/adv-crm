<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * AsaasService
 *
 * Integração com a API do Asaas v3 — Checkout API.
 * Documentação: https://docs.asaas.com/docs/introdução-1
 *
 * Arquitetura:
 *  - Credenciais (api_key, base_url, checkout_url) vêm do MotherShip (infrastructure_nodes type=asaas)
 *  - Ambiente sandbox/produção configurável via MotherShip sem tocar em .env
 *  - Toda cobrança usa POST /v3/checkouts (Asaas Checkout API)
 *  - URL de checkout: {checkout_url}/checkoutSession/show?id={ID_RETORNADO}
 */
class AsaasService
{
    /**
     * Retorna a configuração do nó Asaas armazenada no MotherShip.
     *
     * @return array|null {
     *   api_url: string,      // https://sandbox.asaas.com ou https://api.asaas.com
     *   api_key: string,      // $aact_hmlg_... (sandbox) ou $aact_prod_... (produção)
     *   checkout_url: string, // URL base para montar link de checkout
     *   webhook_token: string // Token para validar header asaas-access-token (opcional)
     * }
     */
    public static function getConfig(): ?array
    {
        return Cache::remember('asaas_node_config', 300, function () {
            $node = InfrastructureNode::on('mothership')
                ->where('type', 'asaas')
                ->first();

            if (!$node) {
                return null;
            }

            $meta = is_array($node->meta_data)
                ? $node->meta_data
                : json_decode($node->meta_data ?? '{}', true);

            return [
                // URL da API REST (sandbox ou produção)
                'api_url'       => rtrim($node->base_url, '/'),
                // ATENÇÃO: a chave começa com $ — nunca interpolar em string PHP com aspas duplas!
                // Ex sandbox: $aact_hmlg_xxx | Ex produção: $aact_prod_xxx
                'api_key'       => $node->api_key,
                // URL de exibição do checkout Asaas
                'checkout_url'  => rtrim($meta['checkout_url'] ?? 'https://sandbox.asaas.com', '/'),
                // Token para validar header 'asaas-access-token' nos webhooks (opcional mas recomendado)
                // Configurar em: painel Asaas → Integrações → Mecanismos de segurança
                // E armazenar em: meta_data.webhook_token no MotherShip
                'webhook_token' => $meta['webhook_token'] ?? null,
            ];
        });
    }

    /**
     * Faz uma chamada HTTP autenticada para a API do Asaas.
     *
     * ATENÇÃO (documentação Asaas):
     *  - O header de autenticação é 'access_token' (NÃO Authorization: Bearer)
     *  - A chave começa com $ (ex: $aact_hmlg_...) — em PHP NUNCA interpolar em aspas duplas
     *  - Sandbox  → prefixo $aact_hmlg_ + URL sandbox.asaas.com
     *  - Produção → prefixo $aact_prod_ + URL api.asaas.com
     */
    private static function request(string $method, string $endpoint, array $data = []): array
    {
        $config = self::getConfig();

        if (!$config) {
            throw new Exception('Configuração do nó Asaas não encontrada no MotherShip.');
        }

        // Valida prefixo da chave × ambiente para detectar configuração incorreta cedo
        $apiKey  = $config['api_key'];
        $baseUrl = $config['api_url'];
        $isSandboxUrl = str_contains($baseUrl, 'sandbox');
        $isSandboxKey = str_starts_with($apiKey, '$aact_hmlg_') || str_starts_with($apiKey, '$aact_YTU5Y');

        if ($isSandboxUrl !== $isSandboxKey) {
            Log::warning('AsaasService: possível uso de chave em ambiente incorreto', [
                'base_url'    => $baseUrl,
                'key_prefix'  => substr($apiKey, 0, 12) . '...',
            ]);
        }

        $url = $baseUrl . '/' . ltrim($endpoint, '/');

        // IMPORTANTE: usar a variável $apiKey (já string PHP) no array — sem interpolação em string dupla
        $http = Http::withHeaders([
            'access_token' => $apiKey,   // header correto: access_token (não Authorization: Bearer)
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        $response = strtoupper($method) === 'GET'
            ? $http->get($url, $data)
            : $http->{strtolower($method)}($url, $data);

        if ($response->failed()) {
            $status = $response->status();
            $errors = $response->json('errors', []);
            $msg    = collect($errors)->pluck('description')->implode(', ');

            // Trata 401 especificamente: chave inválida, expirada ou em ambiente errado
            if ($status === 401) {
                $msg = 'Chave de API Asaas inválida, expirada ou usada no ambiente incorreto (401). '
                     . 'Verifique a chave no MotherShip → infrastructure_nodes (type=asaas). '
                     . 'Sandbox usa $aact_hmlg_..., produção usa $aact_prod_...';
            }

            $msg = $msg ?: 'Erro desconhecido na API Asaas (HTTP ' . $status . ').';

            Log::error('AsaasService::request falhou', [
                'url'        => $url,
                'status'     => $status,
                'response'   => $response->json(),
                'key_prefix' => substr($apiKey, 0, 12) . '...',
            ]);

            throw new Exception($msg);
        }

        // json() pode retornar null se o body não for JSON válido — garantimos array
        return $response->json() ?? [];
    }

    /**
     * Recupera os detalhes de um Payment Link (Checkout) via API do Asaas.
     * Necessário pois o Asaas não envia o externalReference nos webhooks de pagamentos 
     * gerados a partir de checkouts.
     *
     * @param string $id ID do Payment Link (ex: payl_123456)
     * @return array
     */
    public static function getPaymentLink(string $id): array
    {
        $config = self::getConfig();

        if (!$config) {
            throw new Exception('Configuração do nó Asaas não encontrada no MotherShip.');
        }

        $response = Http::withHeaders([
            'access_token' => $config['api_key'],
            'accept'       => 'application/json',
        ])->get($config['api_url'] . "/v3/paymentLinks/{$id}");

        if ($response->failed()) {
            \Log::error("AsaasService::getPaymentLink falhou para o ID {$id}: " . $response->body());
            throw new \Exception("Erro ao buscar Link de Pagamento no Asaas: " . $response->body());
        }

        return $response->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DADOS DO ESCRITÓRIO (core_config → campo usado no customerData)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Lê os dados do escritório do core_config do Krayin e monta
     * o customerData pronto para o checkout Asaas.
     */
    public static function getOwnerCustomerData(): array
    {
        $tenantId = MotherShipService::getTenantId();
        
        // 1. Tenta buscar da nova tabela global de Faturamento SaaS (MotherShip)
        $billing = \SuiteZap\LawFirm\SaaS\Models\TenantBillingInfo::on('mothership')
            ->where('tenant_id', $tenantId)
            ->first();

        if ($billing) {
            return [
                'name'          => $billing->name,
                'cpfCnpj'       => preg_replace('/[^0-9]/', '', $billing->cpf_cnpj),
                'email'         => $billing->email,
                'phone'         => preg_replace('/[^0-9]/', '', $billing->phone),
                'address'       => $billing->address,
                'addressNumber' => $billing->address_number,
                'complement'    => $billing->complement,
                'postalCode'    => preg_replace('/[^0-9]/', '', $billing->postal_code),
                'province'      => $billing->province,
                'city'          => $billing->city,
            ];
        }

        // 2. Fallback legado: tenta buscar do core_config (Banco local do Tenant)
        $get = fn($key) => DB::table('core_config')
            ->where('code', "lawfirm.settings.general.{$key}")
            ->value('value') ?? '';

        return [
            'name'          => $get('company_name'),
            'cpfCnpj'       => preg_replace('/[^0-9]/', '', $get('document_id')),
            'email'         => $get('contact_email'),
            'phone'         => preg_replace('/[^0-9]/', '', $get('contact_whatsapp')),
            'address'       => $get('address_street'),
            'addressNumber' => $get('address_number'),
            'complement'    => $get('address_complement'),
            'postalCode'    => preg_replace('/[^0-9]/', '', $get('address_cep')),
            'province'      => $get('address_province'),
            'city'          => $get('city'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHECKOUT API — POST /v3/checkouts
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Cria um Checkout para compra avulsa de Créditos de IA.
     *
     * Aceita PIX e Cartão de Crédito (à vista).
     * Retorna a URL completa da tela de checkout.
     *
     * @param  int    $credits     Quantidade de créditos do pacote
     * @param  float  $price       Valor em reais
     * @param  array  $customerData Dados do cliente (já montados por getOwnerCustomerData())
     * @return string URL do checkout
     */
    public static function createCreditCheckout(int $credits, float $price, array $customerData, string $paymentMethod = 'PIX'): string
    {
        $config   = self::getConfig();
        $tenantId = MotherShipService::getTenantId();

        $billingTypes = ['PIX'];
        $chargeTypes = ['DETACHED'];

        if ($paymentMethod === 'CREDIT_CARD') {
            $billingTypes = ['CREDIT_CARD'];
            $chargeTypes = ['DETACHED'];
        } elseif ($paymentMethod === 'CREDIT_CARD_INSTALLMENT') {
            $billingTypes = ['CREDIT_CARD'];
            $chargeTypes = ['DETACHED', 'INSTALLMENT']; // Asaas exige DETACHED junto com INSTALLMENT no array
        }

        $payload = [
            'billingTypes'    => $billingTypes,
            'chargeTypes'     => $chargeTypes,
            // Link válido por 60 minutos
            'minutesToExpire' => 60,
            'callback'        => [
                'successUrl' => route('admin.lawfirm.saas.index') . '?payment=success',
                'cancelUrl'  => route('admin.lawfirm.saas.index') . '?payment=cancelled',
                'expiredUrl' => route('admin.lawfirm.saas.index') . '?payment=expired',
            ],
            'items' => [
                [
                    'name'        => "Pacote de {$credits} Créditos de IA",
                    'description' => "Recarga de créditos para os Assistentes de IA do LawFirm CRM. Tenant: {$tenantId}",
                    'quantity'    => 1,
                    'value'       => $price,
                ],
            ],
            // Referência externa para correlacionar no webhook
            'externalReference' => "{$tenantId}|credit|{$credits}",
            'customerData'     => $customerData,
        ];

        $result = self::request('post', 'v3/checkouts', $payload);

        if (empty($result['id'])) {
            Log::error('Asaas Checkout Response Missing ID', ['result' => $result]);
            throw new Exception('Asaas não retornou ID de checkout válido.');
        }

        return $config['checkout_url'] . '/checkoutSession/show?id=' . $result['id'];
    }

    /**
     * Cria um Checkout para Assinatura Mensal (recorrente) do plano.
     *
     * Aceita apenas Cartão de Crédito pois PIX não é compatível com chargeType RECURRENT.
     * Retorna a URL completa da tela de checkout.
     *
     * @param  string $planId   Identificador do plano (ex: 'pro_mensal')
     * @param  float  $price    Valor mensal em reais
     * @param  string $planName Nome de exibição do plano
     * @param  array  $customerData Dados do cliente
     * @return string URL do checkout
     */
    public static function createSubscriptionCheckout(string $planId, float $price, string $planName, array $customerData): string
    {
        $config   = self::getConfig();
        $tenantId = MotherShipService::getTenantId();

        $payload = [
            // Assinatura recorrente — apenas Cartão de Crédito suportado
            'billingTypes'    => ['CREDIT_CARD'],
            'chargeTypes'     => ['RECURRENT'],
            'minutesToExpire' => 120,
            'callback'        => [
                'successUrl' => route('admin.lawfirm.saas.index') . '?payment=success&plan=' . $planId,
                'cancelUrl'  => route('admin.lawfirm.saas.index') . '?payment=cancelled',
                'expiredUrl' => route('admin.lawfirm.saas.index') . '?payment=expired',
            ],
            'items' => [
                [
                    'name'        => $planName,
                    'description' => "Assinatura mensal do plano {$planName} — LawFirm CRM",
                    'quantity'    => 1,
                    'value'       => $price,
                ],
            ],
            // Configuração da recorrência
            'subscription' => [
                'cycle'       => 'MONTHLY',
                'nextDueDate' => now()->addDay()->format('Y-m-d H:i:s'),
            ],
            'externalReference' => "{$tenantId}|subscription|{$planId}",
            'customerData'     => $customerData,
        ];

        $result = self::request('post', 'v3/checkouts', $payload);

        if (empty($result['id'])) {
            throw new Exception('Asaas não retornou ID de checkout válido.');
        }

        return $config['checkout_url'] . '/checkoutSession/show?id=' . $result['id'];
    }

    /**
     * Cria um Checkout avulso (PIX ou Cartão) para pagamento único.
     * Método genérico utilizado por outros fluxos.
     */
    public static function createDetachedCheckout(
        string $name,
        string $description,
        float  $price,
        array  $billingTypes,
        array  $customerData,
        string $externalReference = ''
    ): string {
        $config = self::getConfig();

        $payload = [
            'billingTypes'    => $billingTypes,
            'chargeTypes'     => ['DETACHED'],
            'minutesToExpire' => 60,
            'callback'        => [
                'successUrl' => route('admin.lawfirm.saas.index') . '?payment=success',
                'cancelUrl'  => route('admin.lawfirm.saas.index') . '?payment=cancelled',
                'expiredUrl' => route('admin.lawfirm.saas.index') . '?payment=expired',
            ],
            'items' => [
                [
                    'name'        => $name,
                    'description' => $description,
                    'quantity'    => 1,
                    'value'       => $price,
                ],
            ],
            'customerData' => $customerData,
        ];

        if ($externalReference) {
            $payload['externalReference'] = $externalReference;
        }

        $result = self::request('post', 'v3/checkouts', $payload);

        if (empty($result['id'])) {
            throw new Exception('Asaas não retornou ID de checkout válido.');
        }

        return $config['checkout_url'] . '/checkoutSession/show?id=' . $result['id'];
    }
}
