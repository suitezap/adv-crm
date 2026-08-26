<?php

namespace SuiteZap\LawFirm\TenantFinance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasSetting;
use SuiteZap\LawFirm\TenantFinance\Models\TenantInvoice;

/**
 * TenantAsaasWebhookController
 *
 * Receptor de webhooks do Asaas do escritório (tenant).
 * Rota pública, isenta de CSRF.
 *
 * Eventos tratados:
 *  - PAYMENT_RECEIVED / PAYMENT_CONFIRMED → status = RECEIVED
 *  - PAYMENT_OVERDUE → status = OVERDUE
 *  - PAYMENT_DELETED / PAYMENT_REFUNDED → status != RECEIVED
 */
class TenantAsaasWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $event = $payload['event'] ?? null;
        $payment = $payload['payment'] ?? [];

        if (! $event || empty($payment['id'])) {
            return response()->json(['status' => 'ignored', 'reason' => 'no event or payment id'], 200);
        }

        $asaasPaymentId = $payment['id'];

        // Localizar invoice local pelo asaas_payment_id
        $invoice = TenantInvoice::where('asaas_payment_id', $asaasPaymentId)->first();

        if (! $invoice) {
            Log::info('[TenantAsaas Webhook] Invoice não encontrada para payment_id: '.$asaasPaymentId);

            return response()->json(['status' => 'ignored', 'reason' => 'invoice not found'], 200);
        }

        // Validar webhook_token se configurado
        $settings = TenantAsaasSetting::where('is_active', true)->first();
        if ($settings && ! empty($settings->webhook_token)) {
            $headerToken = $request->header('asaas-access-token');
            if ($headerToken !== $settings->webhook_token) {
                Log::error('[TenantAsaas Webhook] Token inválido.', [
                    'expected' => substr($settings->webhook_token, 0, 6).'...',
                    'received' => substr($headerToken ?? '', 0, 6).'...',
                ]);

                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        // Processar evento
        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED_IN_CASH_UNDONE':
                $invoice->update([
                    'status'       => 'RECEIVED',
                    'payment_date' => $payment['paymentDate'] ?? $payment['confirmedDate'] ?? now(),
                ]);

                // Sincronizar com law_financials se vinculado
                if ($invoice->financial_id) {
                    Financial::where('id', $invoice->financial_id)
                        ->update([
                            'status'       => 'pago',
                            'payment_date' => $payment['paymentDate'] ?? now(),
                        ]);
                }
                break;

            case 'PAYMENT_OVERDUE':
                $invoice->update(['status' => 'OVERDUE']);
                break;

            case 'PAYMENT_DELETED':
            case 'PAYMENT_RESTORED':
                $invoice->update(['status' => 'CANCELED']);
                break;

            case 'PAYMENT_REFUNDED':
                $invoice->update(['status' => 'REFUNDED']);
                break;

            default:
                Log::info('[TenantAsaas Webhook] Evento não tratado: '.$event);

                return response()->json(['status' => 'ignored', 'event' => $event], 200);
        }

        Log::info('[TenantAsaas Webhook] Processado', [
            'event'      => $event,
            'payment_id' => $asaasPaymentId,
            'invoice_id' => $invoice->id,
            'new_status' => $invoice->status,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
