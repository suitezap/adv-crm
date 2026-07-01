<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Whatsapp\Services\MessengerService;

/**
 * Receives raw POST callbacks from the Evolution API server.
 * Route is registered OUTSIDE the authenticated middleware group (CSRF-exempt).
 *
 * Diagnostic logging is intentionally verbose to assist with production debugging.
 * Set LOG_LEVEL=debug in .env or filter with: tail -f storage/logs/laravel.log | grep WhatsappWebhook
 */
class WhatsappWebhookController extends Controller
{
    public function __construct(private MessengerService $messenger) {}

    public function handle(Request $request, int $tenantId): \Illuminate\Http\JsonResponse
    {
        $event = $request->input('event');
        $payload = $request->all();

        // ── Diagnostic: log every incoming webhook hit ────────────────────────
        Log::channel('stack')->info('[WhatsappWebhook] HIT', [
            'tenant_id'    => $tenantId,
            'event'        => $event,
            'ip'           => $request->ip(),
            'payload_keys' => array_keys($payload),
        ]);

        // ── Guard: reject unknown tenants ─────────────────────────────────────
        if ($tenantId <= 0) {
            Log::warning('[WhatsappWebhook] Invalid tenant_id received.', compact('tenantId'));

            return response()->json(['ok' => false, 'error' => 'invalid_tenant'], 400);
        }

        // ── MESSAGES_UPSERT ───────────────────────────────────────────────────
        if ($event === 'messages.upsert') {
            $messages = $request->input('data.messages', []);

            if (empty($messages)) {
                Log::warning('[WhatsappWebhook] messages.upsert received but data.messages is empty.', [
                    'tenant_id' => $tenantId,
                    'raw_data'  => $request->input('data'),
                ]);
            }

            foreach ($messages as $rawMessage) {
                try {
                    $msg = $this->messenger->processIncoming($tenantId, $rawMessage);
                    if ($msg) {
                        Log::info('[WhatsappWebhook] Message saved.', [
                            'tenant_id' => $tenantId,
                            'msg_id'    => $msg->id,
                            'evo_id'    => $msg->evolution_message_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('[WhatsappWebhook] Error processing message.', [
                        'tenant_id' => $tenantId,
                        'error'     => $e->getMessage(),
                        'trace'     => $e->getTraceAsString(),
                        'raw'       => $rawMessage,
                    ]);
                }
            }
        }

        // ── MESSAGES_UPDATE (ACK) ─────────────────────────────────────────────
        elseif ($event === 'messages.update') {
            $updates = $request->input('data', []);

            foreach ($updates as $update) {
                $msgId = $update['key']['id'] ?? null;
                $ack = $update['update']['status'] ?? null;

                if ($msgId && $ack !== null) {
                    try {
                        $this->messenger->updateAck($msgId, (int) $ack);
                        Log::info('[WhatsappWebhook] ACK updated.', compact('msgId', 'ack'));
                    } catch (\Exception $e) {
                        Log::error('[WhatsappWebhook] ACK update failed.', [
                            'msgId' => $msgId,
                            'ack'   => $ack,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // ── Unhandled event type (informational only) ─────────────────────────
        else {
            Log::info('[WhatsappWebhook] Unhandled event type received.', [
                'tenant_id' => $tenantId,
                'event'     => $event,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
