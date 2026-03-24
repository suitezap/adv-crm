<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Services\AsaasService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Illuminate\Support\Facades\Log;

/**
 * AsaasWebhookController
 *
 * Processa notificações de eventos de pagamento enviadas pelo Asaas.
 *
 * ─── Segurança (documentação Asaas) ─────────────────────────────────────────
 * O Asaas envia um token de autenticação no header:
 *   asaas-access-token: <token_configurado_no_painel_asaas>
 *
 * Este token é opcional mas altamente recomendado. Deve ser configurado no
 * painel Asaas em: Menu do usuário → Integrações → Mecanismos de segurança
 * e armazenado no MotherShip (meta_data.webhook_token do nó Asaas).
 *
 * ─── Fluxo de Validação de Saque ────────────────────────────────────────────
 * Para transferências/saques, o Asaas envia: POST 5s após a criação.
 * Máximo 3 tentativas → cancelamento automático na 3ª falha.
 * Resposta deve ser 200 para aprovar, qualquer outro para rejeitar.
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
        // O Asaas envia ACCESS_TOKEN_EXPIRING antes de expirar (ciclo de vida)
        if ($event === 'ACCESS_TOKEN_EXPIRING') {
            Log::warning('AsaasWebhook: CHAVE DE API PRÓXIMA DA EXPIRAÇÃO! Renove em: Integrações → Chaves de API.', [
                'payload' => $payload,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Valida o token de autenticação do webhook.
     * O token é enviado no header 'asaas-access-token' pelo Asaas.
     * Deve ser configurado no painel Asaas e armazenado em meta_data.webhook_token.
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

    private function handlePayment(?array $payment): void
    {
        if (!$payment) return;

        $externalReference = $payment['externalReference'] ?? null;

        // Asaas /v3/checkouts geram Payment Links. O webhook PAYMENT_RECEIVED traz 
        // o ID do link em 'paymentLink', mas NÃO herda o externalReference automaticamente.
        if (!$externalReference && !empty($payment['paymentLink'])) {
            try {
                $linkId = $payment['paymentLink'];
                $linkData = AsaasService::getPaymentLink($linkId);
                $externalReference = $linkData['externalReference'] ?? null;
            } catch (\Exception $e) {
                Log::error("AsaasWebhook: Erro ao buscar dados do PaymentLink {$payment['paymentLink']}: " . $e->getMessage());
            }
        }

        if (!$externalReference) {
            Log::warning('AsaasWebhook: pagamento sem externalReference (e sem link).', ['id' => $payment['id'] ?? '']);
            return;
        }

        // Formato: {tenantId}|{type}|{value}
        // Exemplo: "42|credit|500" ou "42|subscription|pro_anual"
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

        try {
            if ($type === 'credit') {
                $creditsToAdd = (float) $valueStr;
                $subscription->ai_tokens_balance += $creditsToAdd;
                $subscription->save();
                Log::info("AsaasWebhook: +{$creditsToAdd} créditos → tenant {$tenantId}. Saldo: {$subscription->ai_tokens_balance}");
            } elseif ($type === 'subscription') {
                $subscription->status = 'active';
                $subscription->save();
                Log::info("AsaasWebhook: assinatura renovada → tenant {$tenantId}, plano: {$valueStr}");
            }

            // Invalida cache do tenant para refletir mudanças imediatamente
            $this->invalidateCache($tenantId);

        } catch (\Exception $e) {
            Log::error('AsaasWebhook: falha ao processar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Invalida o cache local do tenant para forçar releitura do MotherShip.
     */
    private function invalidateCache(string $tenantId): void
    {
        \Illuminate\Support\Facades\Cache::forget("tenant_{$tenantId}_subscription");
        \Illuminate\Support\Facades\Cache::forget("tenant_{$tenantId}_available_assistants");
        \Illuminate\Support\Facades\Cache::forget('asaas_node_config');
    }
}
