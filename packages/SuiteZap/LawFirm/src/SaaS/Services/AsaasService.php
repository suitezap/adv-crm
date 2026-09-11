<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use SuiteZap\LawFirm\SaaS\Models\SaasOrder;
use SuiteZap\LawFirm\SaaS\Models\SaasTransaction;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Models\TenantBillingInfo;

/**
 * AsaasService
 *
 * Integração com a API do Asaas v3 — Checkout API.
 * Documentação: https://docs.asaas.com/docs/introdução-1
 *
 * Arquitetura (v3.21 — Order-based):
 *  - Credenciais (api_key, base_url, checkout_url) vêm do MotherShip (infrastructure_nodes type=asaas)
 *  - Ambiente sandbox/produção configurável via MotherShip sem tocar em .env
 *  - Toda cobrança usa POST /v3/checkouts (Asaas Checkout API)
 *  - externalReference = "order_{id}" (referência à tabela saas_orders local)
 *  - Proporção fixa: R$ 1,00 = 1 Crédito de IA
 */
class AsaasService
{
    /**
     * Retorna a configuração do nó Asaas armazenada no MotherShip.
     *
     * @return array|null {
     *                    api_url: string,      // https://sandbox.asaas.com ou https://api.asaas.com
     *                    api_key: string,      // $aact_hmlg_... (sandbox) ou $aact_prod_... (produção)
     *                    checkout_url: string, // URL base para montar link de checkout
     *                    webhook_token: string // Token para validar header asaas-access-token (opcional)
     *                    }
     */
    public static function getConfig(): ?array
    {
        return Cache::remember('asaas_node_config', 300, function () {
            $node = InfrastructureNode::on('mothership')
                ->where('type', 'asaas')
                ->first();

            if (! $node) {
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

        if (! $config) {
            throw new Exception('Configuração do nó Asaas não encontrada no MotherShip.');
        }

        // Valida prefixo da chave × ambiente para detectar configuração incorreta cedo
        $apiKey = $config['api_key'];
        $baseUrl = $config['api_url'];
        $isSandboxUrl = str_contains($baseUrl, 'sandbox');
        $isSandboxKey = str_starts_with($apiKey, '$aact_hmlg_') || str_starts_with($apiKey, '$aact_YTU5Y');

        if ($isSandboxUrl !== $isSandboxKey) {
            Log::warning('AsaasService: possível uso de chave em ambiente incorreto', [
                'base_url'    => $baseUrl,
                'key_prefix'  => substr($apiKey, 0, 12).'...',
            ]);
        }

        $url = $baseUrl.'/'.ltrim($endpoint, '/');

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
            $msg = collect($errors)->pluck('description')->implode(', ');

            // Trata 401 especificamente: chave inválida, expirada ou em ambiente errado
            if ($status === 401) {
                $msg = 'Chave de API Asaas inválida, expirada ou usada no ambiente incorreto (401). '
                     .'Verifique a chave no MotherShip → infrastructure_nodes (type=asaas). '
                     .'Sandbox usa $aact_hmlg_..., produção usa $aact_prod_...';
            }

            $msg = $msg ?: 'Erro desconhecido na API Asaas (HTTP '.$status.').';

            Log::error('AsaasService::request falhou', [
                'url'        => $url,
                'status'     => $status,
                'response'   => $response->json(),
                'key_prefix' => substr($apiKey, 0, 12).'...',
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
     * @param  string  $id  ID do Payment Link (ex: payl_123456)
     */
    public static function getPaymentLink(string $id): array
    {
        $config = self::getConfig();

        if (! $config) {
            throw new Exception('Configuração do nó Asaas não encontrada no MotherShip.');
        }

        $response = Http::withHeaders([
            'access_token' => $config['api_key'],
            'accept'       => 'application/json',
        ])->get($config['api_url']."/v3/paymentLinks/{$id}");

        if ($response->failed()) {
            \Log::error("AsaasService::getPaymentLink falhou para o ID {$id}: ".$response->body());
            throw new Exception('Erro ao buscar Link de Pagamento no Asaas: '.$response->body());
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
        $billing = TenantBillingInfo::on('mothership')
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
        $get = fn ($key) => DB::table('core_config')
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
    // CHECKOUT API — POST /v3/checkouts (v3.21 — Order-based)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Cria um Checkout para compra avulsa de Créditos de IA.
     *
     * Proporção fixa: R$ 1,00 = 1 Crédito.
     *
     * @param  float  $value  Valor em R$ (= quantidade de créditos)
     * @param  array  $customerData  Dados do cliente (já montados por getOwnerCustomerData())
     * @param  string  $paymentMethod  PIX, CREDIT_CARD, CREDIT_CARD_INSTALLMENT
     * @param  int  $orderId  ID da SaasOrder local (criada pelo Controller)
     * @return array  { checkout_url: string, session_id: string }
     */
    public static function createCreditCheckout(float $value, array $customerData, string $paymentMethod = 'PIX', int $orderId = 0): array
    {
        $config = self::getConfig();
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

        // Créditos = Valor em R$ (proporção 1:1)
        $credits = $value;

        $payload = [
            'billingTypes'    => $billingTypes,
            'chargeTypes'     => $chargeTypes,
            // Link válido por 60 minutos
            'minutesToExpire' => 60,
            'callback'        => [
                'successUrl' => route('admin.lawfirm.saas.index').'?payment=success',
                'cancelUrl'  => route('admin.lawfirm.saas.index').'?payment=cancelled',
                'expiredUrl' => route('admin.lawfirm.saas.index').'?payment=expired',
            ],
            'items' => [
                [
                    'name'        => 'Créditos IA (R$ '.number_format($value, 2, ',', '.').')',
                    'description' => "Recarga de créditos para os Assistentes de IA do LawFirm CRM. Tenant: {$tenantId}",
                    'quantity'    => 1,
                    'value'       => $value,
                ],
            ],
            // v3.21: externalReference baseado na Order local
            'externalReference' => "order_{$orderId}",
            'customerData'      => $customerData,
        ];

        $result = self::request('post', 'v3/checkouts', $payload);

        if (empty($result['id'])) {
            Log::error('Asaas Checkout Response Missing ID', ['result' => $result]);
            throw new Exception('Asaas não retornou ID de checkout válido.');
        }

        return [
            'checkout_url' => $config['checkout_url'].'/checkoutSession/show?id='.$result['id'],
            'session_id'   => $result['id'],
        ];
    }

    /**
     * Cria um Checkout para Assinatura Mensal (recorrente) do plano.
     *
     * Aceita apenas Cartão de Crédito pois PIX não é compatível com chargeType RECURRENT.
     *
     * @param  string  $planId  Identificador do plano (ex: 'pro_anual')
     * @param  float  $price  Valor mensal em reais
     * @param  string  $planName  Nome de exibição do plano
     * @param  array  $customerData  Dados do cliente
     * @param  int  $orderId  ID da SaasOrder local
     * @return array  { checkout_url: string, session_id: string }
     */
    public static function createSubscriptionCheckout(string $planId, float $price, string $planName, array $customerData, int $orderId = 0): array
    {
        $config = self::getConfig();
        $tenantId = MotherShipService::getTenantId();

        $payload = [
            // Assinatura recorrente — apenas Cartão de Crédito suportado
            'billingTypes'    => ['CREDIT_CARD'],
            'chargeTypes'     => ['RECURRENT'],
            'minutesToExpire' => 120,
            'callback'        => [
                'successUrl' => route('admin.lawfirm.saas.index').'?payment=success&plan='.$planId,
                'cancelUrl'  => route('admin.lawfirm.saas.index').'?payment=cancelled',
                'expiredUrl' => route('admin.lawfirm.saas.index').'?payment=expired',
            ],
            'items' => [
                [
                    'name'        => mb_substr($planName, 0, 30),
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
            // v3.21: externalReference baseado na Order local
            'externalReference' => "order_{$orderId}",
            'customerData'      => $customerData,
        ];

        $result = self::request('post', 'v3/checkouts', $payload);

        if (empty($result['id'])) {
            throw new Exception('Asaas não retornou ID de checkout válido.');
        }

        return [
            'checkout_url' => $config['checkout_url'].'/checkoutSession/show?id='.$result['id'],
            'session_id'   => $result['id'],
        ];
    }

    /**
     * Cria um Checkout avulso (PIX ou Cartão) para pagamento único.
     * Método genérico utilizado por outros fluxos.
     */
    public static function createDetachedCheckout(
        string $name,
        string $description,
        float $price,
        array $billingTypes,
        array $customerData,
        string $externalReference = ''
    ): string {
        $config = self::getConfig();

        $payload = [
            'billingTypes'    => $billingTypes,
            'chargeTypes'     => ['DETACHED'],
            'minutesToExpire' => 60,
            'callback'        => [
                'successUrl' => route('admin.lawfirm.saas.index').'?payment=success',
                'cancelUrl'  => route('admin.lawfirm.saas.index').'?payment=cancelled',
                'expiredUrl' => route('admin.lawfirm.saas.index').'?payment=expired',
            ],
            'items' => [
                [
                    'name'        => mb_substr($name, 0, 30),
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

        return $config['checkout_url'].'/checkoutSession/show?id='.$result['id'];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SYNC ATIVO — Fallback para ambientes onde o webhook não chega
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Sincroniza pagamentos recentes do Asaas que ainda não foram processados.
     * Resolve o problema de testes locais onde o webhook não chega, processando idempoticamente.
     *
     * v3.21: Primeiro tenta resolver via SaasOrder (novo formato "order_{id}").
     * Fallback: mantém parser legado "{tenantId}|tipo|valor" para pagamentos em trânsito.
     */
    public static function syncTenantPayments(): void
    {
        try {
            $config = self::getConfig();
            $tenantId = MotherShipService::getTenantId();

            if (! $config || ! $tenantId) {
                return;
            }

            Log::info("AsaasService::syncTenantPayments: iniciando sync para tenant {$tenantId}.");

            // Busca pagamentos RECEIVED/CONFIRMED dos últimos 30 dias.
            $dateFrom = now()->subDays(30)->format('Y-m-d');

            $response = Http::withHeaders([
                'access_token' => $config['api_key'],
                'accept'       => 'application/json',
            ])->get($config['api_url'].'/v3/payments', [
                'status'          => 'RECEIVED,CONFIRMED',
                'dateCreatedFrom' => $dateFrom,
                'limit'           => 50,
            ]);

            if ($response->failed()) {
                Log::warning('AsaasService::syncTenantPayments: falha na chamada à API Asaas.', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return;
            }

            $payments = $response->json('data', []);
            Log::info('AsaasService::syncTenantPayments: {'.count($payments).'} pagamentos encontrados na API.');

            foreach ($payments as $payment) {
                $paymentId = $payment['id'] ?? null;
                if (! $paymentId) {
                    continue;
                }

                // --- IDEMPOTÊNCIA: verifica se já foi processado PARA ESTE TENANT ---
                $transactionExists = SaasTransaction::where('reference_type', 'asaas_payment')
                    ->where('reference_id', $paymentId)
                    ->where('tenant_id', $tenantId)
                    ->exists();

                if ($transactionExists) {
                    continue;
                }

                // Tenta obter o externalReference — primeiro do payment, depois do PaymentLink
                $externalReference = $payment['externalReference'] ?? null;

                if (! $externalReference && ! empty($payment['paymentLink'])) {
                    try {
                        $linkData = self::getPaymentLink($payment['paymentLink']);
                        $externalReference = $linkData['externalReference'] ?? null;
                    } catch (Exception $e) {
                        Log::warning("AsaasService::sync: erro ao buscar PaymentLink {$payment['paymentLink']}: ".$e->getMessage());
                    }
                }

                // ── NOVO (v3.21): Tenta resolver via SaasOrder ──────────
                if ($externalReference && str_starts_with($externalReference, 'order_')) {
                    self::processOrderBasedPayment($externalReference, $payment, $tenantId);

                    continue;
                }

                // ── Tenta recuperar via sessão de checkout salva em SaasOrder ──
                if (! $externalReference && ! empty($payment['checkoutSession'])) {
                    $order = SaasOrder::where('asaas_checkout_session_id', $payment['checkoutSession'])
                        ->where('tenant_id', $tenantId)
                        ->where('status', 'PENDING')
                        ->first();

                    if ($order) {
                        self::processOrderBasedPayment("order_{$order->id}", $payment, $tenantId);

                        continue;
                    }
                }

                // ── FALLBACK LEGADO: formato "{tenantId}|tipo|valor" ─────
                if ($externalReference) {
                    self::processLegacyPayment($externalReference, $payment, $tenantId);

                    continue;
                }

                Log::warning('AsaasService::sync: pagamento sem externalReference identificável — ignorado.', [
                    'payment_id' => $paymentId,
                    'tenant_id'  => $tenantId,
                ]);
            }
        } catch (Exception $e) {
            Log::error('AsaasService::syncTenantPayments falhou: '.$e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROCESSADORES DE PAGAMENTO INTERNOS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Processa um pagamento baseado em SaasOrder (v3.21+).
     * Formato do externalReference: "order_{id}"
     */
    public static function processOrderBasedPayment(string $externalReference, array $payment, string $tenantId): void
    {
        $orderId = (int) str_replace('order_', '', $externalReference);

        $order = SaasOrder::where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $order) {
            Log::warning("AsaasService: order #{$orderId} não encontrada para tenant {$tenantId}.");

            return;
        }

        // Idempotência: se já foi pago, ignora
        if ($order->isPaid()) {
            Log::info("AsaasService: order #{$orderId} já processada (PAID).");

            return;
        }

        $paymentId = $payment['id'];

        // Busca a subscription do MotherShip
        $subscription = Subscription::on('mothership')
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $subscription) {
            Log::error("AsaasService: assinatura não encontrada para tenant {$tenantId}.");

            return;
        }

        // Marca a order como PAID
        $order->markAsPaid($paymentId);

        if ($order->type === 'ai_credits') {
            // Converte BRL → SuiteCoins (taxa configurável via MotherShip)
            $brlAmount = (float) $order->value;
            $suitecoinsVisual = SuiteCoinService::toVirtual($brlAmount);
            $subscription->suitecoin_balance += $brlAmount;
            $subscription->save();

            $invoiceInfo = '';
            if (! empty($payment['invoiceNumber'])) {
                $invoiceInfo = " - Fatura Asaas: {$payment['invoiceNumber']}";
            }

            SaasTransaction::create([
                'tenant_id'      => $tenantId,
                'user_id'        => $order->user_id,
                'type'           => 'credit',
                'amount'         => $brlAmount,
                'balance_after'  => $subscription->suitecoin_balance,
                'currency'       => SuiteCoinService::CURRENCY_CODE,
                'service_type'   => 'asaas_checkout',
                'description'    => 'Recarga de '.SuiteCoinService::format($suitecoinsVisual)." via Asaas ({$paymentId}){$invoiceInfo}",
                'reference_id'   => $paymentId,
                'reference_type' => 'asaas_payment',
            ]);

            Log::info('AsaasService: +'.SuiteCoinService::format($suitecoinsVisual)." (R$ {$brlAmount}) → tenant {$tenantId}, user #{$order->user_id}. Saldo DB(BRL): {$subscription->suitecoin_balance}");

        } elseif ($order->type === 'subscription') {
            $subscription->status = 'active';
            $subscription->save();
            Log::info("AsaasService: assinatura renovada → tenant {$tenantId} via order #{$orderId}.");
        }

        // Invalida cache
        Cache::forget("tenant_{$tenantId}_subscription");
        Cache::forget("tenant_{$tenantId}_available_assistants");
        Cache::forget('asaas_node_config');
    }

    /**
     * Processa um pagamento no formato legado "{tenantId}|tipo|valor".
     * Mantido para retrocompatibilidade com pagamentos em trânsito.
     */
    private static function processLegacyPayment(string $externalReference, array $payment, string $tenantId): void
    {
        $parts = explode('|', $externalReference);
        if (count($parts) < 3) {
            Log::warning('AsaasService::sync: externalReference legado mal formatado.', ['ref' => $externalReference]);

            return;
        }

        [$refTenantId, $type, $valueStr] = $parts;

        // Garante que o pagamento pertence EXATAMENTE a este tenant
        if ($refTenantId !== $tenantId) {
            Log::warning('AsaasService::sync: pagamento com tenant_id diferente do atual — ignorado.', [
                'payment_id'    => $payment['id'],
                'ref_tenant_id' => $refTenantId,
                'current_tenant'=> $tenantId,
            ]);

            return;
        }

        $subscription = Subscription::on('mothership')
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $subscription) {
            Log::error("AsaasService::sync: assinatura não encontrada para tenant {$tenantId}.");

            return;
        }

        $paymentId = $payment['id'];

        if ($type === 'credit') {
            $brlAmount = (float) $valueStr;
            $suitecoinsVisual = SuiteCoinService::toVirtual($brlAmount);
            $subscription->suitecoin_balance += $brlAmount;
            $subscription->save();

            $invoiceInfo = '';
            if (! empty($payment['invoiceNumber'])) {
                $invoiceInfo = " - Fatura Asaas: {$payment['invoiceNumber']}";
            }

            SaasTransaction::create([
                'tenant_id'      => $tenantId,
                'type'           => 'credit',
                'amount'         => $brlAmount,
                'balance_after'  => $subscription->suitecoin_balance,
                'currency'       => SuiteCoinService::CURRENCY_CODE,
                'service_type'   => 'asaas_checkout',
                'description'    => 'Recarga de '.SuiteCoinService::format($suitecoinsVisual)." via Asaas ({$paymentId}){$invoiceInfo} - Legado",
                'reference_id'   => $paymentId,
                'reference_type' => 'asaas_payment',
            ]);

            Log::info('AsaasService::sync (legado): +'.SuiteCoinService::format($suitecoinsVisual)." sincronizados para tenant {$tenantId}.");
            Cache::forget("tenant_{$tenantId}_subscription");
            Cache::forget("tenant_{$tenantId}_available_assistants");
        }
    }
}
