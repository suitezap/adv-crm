<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use SuiteZap\LawFirm\SaaS\Models\SaasOrder;
use SuiteZap\LawFirm\SaaS\Models\SaasTransaction;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Services\AsaasService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * AsaasWebhookController
 *
 * Processa notificações de eventos de pagamento enviadas pelo Asaas.
 *
 * v3.21: Prioriza resolução via SaasOrder (externalReference = "order_{id}").
 * Mantém fallback para formato legado "{tenantId}|tipo|valor".
 *
 * ─── Segurança (documentação Asaas) ─────────────────────────────────────────
 * O Asaas envia um token de autenticação no header:
 *   asaas-access-token: <token_configurado_no_painel_asaas>
 *
 * Este token é opcional mas altamente recomendado. Deve ser configurado no
 * painel Asaas em: Menu do usuário → Integrações → Mecanismos de segurança
 * e armazenado no MotherShip (meta_data.webhook_token do nó Asaas).
 */
class AsaasWebhookController extends Controller
{
    /**
     * Recebe e processa eventos do Asaas via webhook.
     */
    public function handle(Request $request)
    {
        // ── 1. Autenticação via asaas-access-token ─────────────────────────
        if (!$this->isAuthorized($request)) {
            Log::warning('AsaasWebhook: token inválido ou ausente.', [
                'ip'     => $request->ip(),
                'header' => substr($request->header('asaas-access-token', ''), 0, 8) . '...',
            ]);
            // Retorna 200 mesmo em falha de auth para não revelar a rota ao atacante
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 200);
        }

        $payload = $request->all();
        $event   = $payload['event'] ?? null;
        $payment = $payload['payment'] ?? null;

        Log::info('AsaasWebhook recebido', [
            'event'     => $event,
            'paymentId' => $payment['id'] ?? null,
        ]);

        // ── 2. Processa pagamentos recebidos/confirmados ────────────────────
        if (in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'])) {
            $this->handlePayment($payment);
        }

        // ── 3. Alerta sobre chave de API próxima da expiração ──────────────
        if ($event === 'ACCESS_TOKEN_EXPIRING') {
            Log::warning('AsaasWebhook: CHAVE DE API PRÓXIMA DA EXPIRAÇÃO! Renove em: Integrações → Chaves de API.', [
                'payload' => $payload,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Valida o token de autenticação do webhook.
     */
    private function isAuthorized(Request $request): bool
    {
        $config = AsaasService::getConfig();

        // Se não há webhook_token configurado, aceita sem validar (menos seguro)
        $expectedToken = $config['webhook_token'] ?? null;
        if (!$expectedToken) {
            return true;
        }

        $receivedToken = $request->header('asaas-access-token');
        return hash_equals($expectedToken, (string) $receivedToken);
    }

    /**
     * Processa um pagamento recebido/confirmado do Asaas.
     *
     * Fluxo de resolução (v3.21):
     *  1. Tenta resolver via SaasOrder (externalReference = "order_{id}")
     *  2. Tenta resolver via checkoutSession salvo na SaasOrder
     *  3. Fallback legado: "{tenantId}|tipo|valor"
     *  4. Fallback definitivo: single-tenant Asaas (valor como crédito)
     */
    private function handlePayment(?array $payment): void
    {
        if (!$payment) return;

        $paymentId = $payment['id'] ?? null;
        if (!$paymentId) return;

        $externalReference = $payment['externalReference'] ?? null;
        $tenantId = MotherShipService::getTenantId();

        // ── Recupera externalReference do PaymentLink se necessário ──────
        if (!$externalReference && !empty($payment['paymentLink'])) {
            try {
                $linkData = AsaasService::getPaymentLink($payment['paymentLink']);
                $externalReference = $linkData['externalReference'] ?? null;
            } catch (\Exception $e) {
                Log::error("AsaasWebhook: Erro ao buscar dados do PaymentLink {$payment['paymentLink']}: " . $e->getMessage());
            }
        }

        // ── ROTA 1 (v3.21): Order-based ("order_{id}") ──────────────────
        if ($externalReference && str_starts_with($externalReference, 'order_')) {
            AsaasService::processOrderBasedPayment($externalReference, $payment, $tenantId);
            return;
        }

        // ── ROTA 2: Tenta resolver via checkoutSession → SaasOrder ──────
        if (!$externalReference && !empty($payment['checkoutSession'])) {
            $order = SaasOrder::where('asaas_checkout_session_id', $payment['checkoutSession'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'PENDING')
                ->first();

            if ($order) {
                AsaasService::processOrderBasedPayment("order_{$order->id}", $payment, $tenantId);
                return;
            }
        }

        // ── ROTA 3: Formato legado "{tenantId}|tipo|valor" ──────────────
        if ($externalReference) {
            $this->handleLegacyPayment($externalReference, $payment);
            return;
        }

        // ── ROTA 4: Fallback definitivo (single-tenant Asaas) ───────────
        if ($tenantId && !empty($payment['value'])) {
            $creditsFromValue = (float)$payment['value'];
            $fakeRef = "{$tenantId}|credit|{$creditsFromValue}";
            Log::info("AsaasWebhook: fallback externalReference criado a partir do valor R$ {$payment['value']} -> {$creditsFromValue} créditos.");
            $this->handleLegacyPayment($fakeRef, $payment);
            return;
        }

        Log::warning('AsaasWebhook: pagamento sem externalReference e sem forma de identificar tenant.', ['id' => $paymentId]);
    }

    /**
     * Processa pagamento no formato legado "{tenantId}|tipo|valor".
     * Mantido para retrocompatibilidade com pagamentos gerados antes da v3.21.
     */
    private function handleLegacyPayment(string $externalReference, array $payment): void
    {
        $parts = explode('|', $externalReference);
        if (count($parts) < 3) {
            Log::warning('AsaasWebhook: externalReference mal formatado.', ['ref' => $externalReference]);
            return;
        }

        [$tenantId, $type, $valueStr] = $parts;

        $subscription = Subscription::on('mothership')
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$subscription) {
            Log::error("AsaasWebhook: assinatura não encontrada para tenant {$tenantId}.");
            return;
        }

        // --- IDEMPOTENCY CHECK (com escopo de tenant) ---
        $transactionExists = SaasTransaction::where('reference_type', 'asaas_payment')
            ->where('reference_id', $payment['id'])
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($transactionExists) {
            Log::info("AsaasWebhook: Pagamento {$payment['id']} já processado anteriormente para tenant {$tenantId}.");
            return;
        }

        try {
            if ($type === 'credit') {
                $creditsToAdd = (float) $valueStr;
                $subscription->ai_tokens_balance += $creditsToAdd;
                $subscription->save();

                $invoiceInfo = '';
                if (!empty($payment['invoiceNumber'])) {
                    $invoiceInfo = " - Fatura Asaas: {$payment['invoiceNumber']}";
                }

                SaasTransaction::create([
                    'tenant_id' => $tenantId,
                    'type' => 'credit',
                    'amount' => $payment['value'] ?? $creditsToAdd,
                    'balance_after' => $subscription->ai_tokens_balance,
                    'service_type' => 'asaas_webhook',
                    'description' => "Recarga de {$creditsToAdd} Créditos de IA via Asaas ({$payment['id']}){$invoiceInfo} - Legado",
                    'reference_id' => $payment['id'],
                    'reference_type' => 'asaas_payment',
                ]);

                Log::info("AsaasWebhook (legado): +{$creditsToAdd} créditos → tenant {$tenantId}. Saldo: {$subscription->ai_tokens_balance}");

            } elseif ($type === 'subscription') {
                $subscription->status = 'active';
                $subscription->save();
                Log::info("AsaasWebhook (legado): assinatura renovada → tenant {$tenantId}, plano: {$valueStr}");
            }

            // Invalida cache do tenant
            $this->invalidateCache($tenantId);

        } catch (\Exception $e) {
            Log::error('AsaasWebhook: falha ao processar pagamento legado: ' . $e->getMessage());
        }
    }

    /**
     * Invalida o cache local do tenant para forçar releitura do MotherShip.
     */
    private function invalidateCache(string $tenantId): void
    {
        Cache::forget("tenant_{$tenantId}_subscription");
        Cache::forget("tenant_{$tenantId}_available_assistants");
        Cache::forget('asaas_node_config');
    }
}
