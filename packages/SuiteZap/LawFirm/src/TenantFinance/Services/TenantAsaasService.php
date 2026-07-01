<?php

namespace SuiteZap\LawFirm\TenantFinance\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasCustomer;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasSetting;
use SuiteZap\LawFirm\TenantFinance\Models\TenantInvoice;

/**
 * TenantAsaasService
 *
 * Client HTTP para a API Asaas v3 operando com as credenciais do TENANT (escritório).
 *
 * ATENÇÃO:
 *  - O header de autenticação é 'access_token' (NÃO Authorization: Bearer)
 *  - A chave começa com $ (ex: $aact_prod_...) — em PHP NUNCA interpolar em aspas duplas
 *  - Sandbox  → prefixo $aact_hmlg_ + URL sandbox.asaas.com
 *  - Produção → prefixo $aact_prod_ + URL api.asaas.com
 *
 * Diferença do AsaasService (SaaS):
 *  - SaaS\Services\AsaasService → plataforma cobra o tenant (infra)
 *  - TenantFinance\Services\TenantAsaasService → tenant cobra seus clientes (negócio)
 */
class TenantAsaasService
{
    protected ?TenantAsaasSetting $settings = null;

    // ── Configuration ────────────────────────────────

    /**
     * Carrega as configurações Asaas do tenant atual.
     */
    public function getSettings(): ?TenantAsaasSetting
    {
        if ($this->settings === null) {
            $this->settings = TenantAsaasSetting::where('is_active', true)->first();
        }

        return $this->settings;
    }

    /**
     * Verifica se o módulo está configurado e ativo.
     */
    public function isConfigured(): bool
    {
        $settings = $this->getSettings();

        return $settings !== null && ! empty($settings->api_key);
    }

    // ── HTTP Client ──────────────────────────────────

    /**
     * Faz uma chamada HTTP autenticada para a API Asaas do tenant.
     *
     * @param  string  $method  POST, GET, PUT, DELETE
     * @param  string  $endpoint  Ex: /v3/customers
     * @param  array  $data  Payload
     * @return array|null Resposta decodificada ou null em caso de erro
     */
    public function request(string $method, string $endpoint, array $data = []): ?array
    {
        $settings = $this->getSettings();

        if (! $settings) {
            Log::error('[TenantAsaas] Credenciais não configuradas para o tenant.');

            return null;
        }

        $url = rtrim($settings->api_url, '/').'/'.ltrim($endpoint, '/');

        try {
            $response = Http::withHeaders([
                'access_token' => $settings->api_key,
                'Content-Type' => 'application/json',
            ])->{strtolower($method)}($url, $data);

            if ($response->failed()) {
                Log::error('[TenantAsaas] API Error', [
                    'status'   => $response->status(),
                    'endpoint' => $endpoint,
                    'body'     => $response->json() ?? $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('[TenantAsaas] Exception', [
                'endpoint' => $endpoint,
                'message'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ── Customers ────────────────────────────────────

    /**
     * Encontra ou cria um cliente no Asaas a partir de um person_id do Krayin.
     *
     * @param  array  $personData  {name, cpfCnpj, email?, phone?}
     */
    public function findOrCreateCustomer(int $personId, array $personData): ?TenantAsaasCustomer
    {
        // 1. Verificar cache local
        $existing = TenantAsaasCustomer::where('person_id', $personId)->first();
        if ($existing) {
            return $existing;
        }

        $mobilePhone = preg_replace('/\D/', '', $personData['phone'] ?? '');
        if (str_starts_with($mobilePhone, '55') && (strlen($mobilePhone) === 12 || strlen($mobilePhone) === 13)) {
            $mobilePhone = substr($mobilePhone, 2);
        }

        // 2. Criar no Asaas
        $response = $this->request('POST', '/v3/customers', [
            'name'                 => $personData['name'],
            'cpfCnpj'              => preg_replace('/\D/', '', $personData['cpfCnpj'] ?? ''),
            'email'                => $personData['email'] ?? null,
            'mobilePhone'          => $mobilePhone,
            'notificationDisabled' => false,
        ]);

        if (! $response || empty($response['id'])) {
            return null;
        }

        // 3. Salvar mapeamento local
        return TenantAsaasCustomer::create([
            'person_id'          => $personId,
            'asaas_customer_id'  => $response['id'],
            'name'               => $personData['name'],
            'cpf_cnpj'           => preg_replace('/\D/', '', $personData['cpfCnpj'] ?? ''),
            'email'              => $personData['email'] ?? null,
            'phone'              => $personData['phone'] ?? null,
        ]);
    }

    // ── Payments (Cobranças) ─────────────────────────

    /**
     * Cria uma cobrança avulsa (single) no Asaas.
     *
     * @param  array  $data  {
     *                       customer_id: string (asaas_customer_id),
     *                       value: float,
     *                       due_date: string (Y-m-d),
     *                       billing_type: string (BOLETO|PIX|CREDIT_CARD),
     *                       description: string,
     *                       external_reference?: string,
     *                       }
     * @return array|null Resposta do Asaas
     */
    public function createPayment(array $data): ?array
    {
        return $this->request('POST', '/v3/payments', [
            'customer'          => $data['customer_id'],
            'billingType'       => $data['billing_type'],
            'value'             => $data['value'],
            'dueDate'           => $data['due_date'],
            'description'       => $data['description'] ?? 'Cobrança Jurídica',
            'externalReference' => $data['external_reference'] ?? null,
        ]);
    }

    /**
     * Cria um parcelamento no Asaas.
     *
     * @param  array  $data  {
     *                       customer_id: string,
     *                       value: float (total),
     *                       installment_count: int,
     *                       due_date: string (Y-m-d),
     *                       billing_type: string,
     *                       description: string,
     *                       }
     */
    public function createInstallment(array $data): ?array
    {
        return $this->request('POST', '/v3/payments', [
            'customer'          => $data['customer_id'],
            'billingType'       => $data['billing_type'],
            'value'             => round($data['value'] / $data['installment_count'], 2),
            'dueDate'           => $data['due_date'],
            'description'       => $data['description'] ?? 'Parcelamento Jurídico',
            'installmentCount'  => $data['installment_count'],
            'installmentValue'  => round($data['value'] / $data['installment_count'], 2),
        ]);
    }

    /**
     * Consulta o status de um pagamento.
     */
    public function getPayment(string $paymentId): ?array
    {
        return $this->request('GET', "/v3/payments/{$paymentId}");
    }

    /**
     * Cancela uma cobrança pendente.
     */
    public function cancelPayment(string $paymentId): ?array
    {
        return $this->request('DELETE', "/v3/payments/{$paymentId}");
    }

    /**
     * Obtém o QR Code PIX de um pagamento.
     */
    public function getPixQrCode(string $paymentId): ?array
    {
        return $this->request('GET', "/v3/payments/{$paymentId}/pixQrCode");
    }

    /**
     * Obtém a linha digitável do boleto.
     */
    public function getIdentificationField(string $paymentId): ?array
    {
        return $this->request('GET', "/v3/payments/{$paymentId}/identificationField");
    }

    // ── Subscriptions (Recorrência) ──────────────────

    /**
     * Cria uma assinatura recorrente no Asaas.
     *
     * @param  array  $data  {
     *                       customer_id: string,
     *                       value: float,
     *                       billing_type: string (CREDIT_CARD por padrão),
     *                       cycle: string (MONTHLY, WEEKLY, etc),
     *                       description: string,
     *                       next_due_date: string (Y-m-d),
     *                       }
     */
    public function createSubscription(array $data): ?array
    {
        return $this->request('POST', '/v3/subscriptions', [
            'customer'    => $data['customer_id'],
            'billingType' => $data['billing_type'] ?? 'CREDIT_CARD',
            'value'       => $data['value'],
            'cycle'       => $data['cycle'] ?? 'MONTHLY',
            'description' => $data['description'] ?? 'Mensalidade Jurídica',
            'nextDueDate' => $data['next_due_date'],
        ]);
    }

    /**
     * Cancela uma assinatura recorrente.
     */
    public function cancelSubscription(string $subscriptionId): ?array
    {
        return $this->request('DELETE', "/v3/subscriptions/{$subscriptionId}");
    }

    // ── Invoice Builder (Orquestração) ───────────────

    /**
     * Orquestra a criação completa de uma cobrança:
     * 1. Find/Create Customer no Asaas
     * 2. Criar Payment/Installment/Subscription no Asaas
     * 3. Salvar TenantInvoice local
     *
     * @param  array  $params  {
     *                         person_id: int,
     *                         person_data: array {name, cpfCnpj, email?, phone?},
     *                         processo_id?: int,
     *                         financial_id?: int,
     *                         type: string (single|installment|subscription),
     *                         value: float,
     *                         due_date: string (Y-m-d),
     *                         billing_type: string,
     *                         description: string,
     *                         installment_count?: int,
     *                         cycle?: string,
     *                         }
     */
    public function createInvoice(array $params): ?TenantInvoice
    {
        // 1. Customer
        $customer = $this->findOrCreateCustomer(
            $params['person_id'],
            $params['person_data']
        );

        if (! $customer) {
            Log::error('[TenantAsaas] Falha ao criar/localizar cliente no Asaas.');

            return null;
        }

        // 2. Criar cobrança na API
        $asaasData = [
            'customer_id'  => $customer->asaas_customer_id,
            'value'        => $params['value'],
            'due_date'     => $params['due_date'],
            'billing_type' => $params['billing_type'],
            'description'  => $params['description'],
        ];

        $response = null;
        $invoiceData = [
            'processo_id'              => $params['processo_id'] ?? null,
            'financial_id'             => $params['financial_id'] ?? null,
            'tenant_asaas_customer_id' => $customer->id,
            'type'                     => $params['type'],
            'description'              => $params['description'],
            'value'                    => $params['value'],
            'billing_type'             => $params['billing_type'],
            'due_date'                 => $params['due_date'],
            'status'                   => 'PENDING',
        ];

        switch ($params['type']) {
            case 'installment':
                $asaasData['installment_count'] = $params['installment_count'];
                $response = $this->createInstallment($asaasData);
                $invoiceData['installment_count'] = $params['installment_count'];
                $invoiceData['installment_value'] = round($params['value'] / $params['installment_count'], 2);
                break;

            case 'subscription':
                $asaasData['cycle'] = $params['cycle'] ?? 'MONTHLY';
                $asaasData['next_due_date'] = $params['due_date'];
                $response = $this->createSubscription($asaasData);
                break;

            default: // single
                $response = $this->createPayment($asaasData);
                break;
        }

        if (! $response || empty($response['id'])) {
            Log::error('[TenantAsaas] Falha ao criar cobrança no Asaas.', [
                'response' => $response,
            ]);

            return null;
        }

        // 3. Mapear resposta
        if ($params['type'] === 'subscription') {
            $invoiceData['asaas_subscription_id'] = $response['id'];
        } else {
            $invoiceData['asaas_payment_id'] = $response['id'];
            $invoiceData['invoice_url'] = $response['invoiceUrl'] ?? null;

            if (isset($response['installment'])) {
                $invoiceData['asaas_installment_id'] = $response['installment'];
            }
        }

        // 4. Buscar QR Code PIX se aplicável
        if ($params['billing_type'] === 'PIX' && ! empty($invoiceData['asaas_payment_id'])) {
            $pixData = $this->getPixQrCode($invoiceData['asaas_payment_id']);
            if ($pixData && isset($pixData['payload'])) {
                $invoiceData['pix_qrcode'] = $pixData['payload'];
            }
        }

        // 5. Persistir localmente
        return TenantInvoice::create($invoiceData);
    }
}
