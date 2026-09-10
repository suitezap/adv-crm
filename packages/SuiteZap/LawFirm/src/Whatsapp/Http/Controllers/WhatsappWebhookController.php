<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use SuiteZap\LawFirm\SaaS\Models\Tenant;
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

    /**
     * Segredo do webhook inbound do tenant (meta_data.webhook_secret do nó
     * Evolution). Null quando não configurado (vale o vínculo instância).
     */
    private function tenantWebhookSecret($tenant): ?string
    {
        try {
            if (empty($tenant->evolution_node_id)) {
                return null;
            }

            $node = InfrastructureNode::on('mothership')->find($tenant->evolution_node_id);

            if (! $node) {
                return null;
            }

            $meta = is_array($node->meta_data)
                ? $node->meta_data
                : (json_decode($node->meta_data, true) ?? []);

            return $meta['webhook_secret'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function handle(Request $request, int $tenantId): JsonResponse
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

        // ── Guard: instance do payload deve pertencer ao tenant da URL ─────────
        // (PRIV-AUDIT-001: sem isso, qualquer um injeta mensagens/ACK em tenant
        // arbitrário por enumeração. Segredo dedicado segue como follow-up em
        // infrastructure_nodes — ver TASKS PRIV-AUDIT-001.)
        try {
            $tenant = Tenant::on('mothership')->where('id', $tenantId)->first();
        } catch (\Exception $e) {
            $tenant = null;
        }

        if (! $tenant) {
            Log::warning('[WhatsappWebhook] Tenant desconhecido.', compact('tenantId'));

            return response()->json(['ok' => false, 'error' => 'invalid_tenant'], 400);
        }

        $payloadInstance = $request->input('instance')
            ?? $request->input('data.instance')
            ?? null;
        $expectedInstances = array_filter([
            $tenant->evolution_instance_name ?? null,
            isset($tenant->evolution_instance_name) ? $tenant->evolution_instance_name.'_atendimento' : null,
            $tenant->evolution_assistente_name ?? null,
        ]);

        if ($payloadInstance !== null && ! in_array($payloadInstance, $expectedInstances, true)) {
            Log::warning('[WhatsappWebhook] Instance fora do tenant.', [
                'tenant_id' => $tenantId,
                'instance'  => $payloadInstance,
            ]);

            return response()->json(['ok' => false, 'error' => 'instance_mismatch'], 400);
        }

        // ── Guard: segredo do webhook quando configurado (fail-closed) ─────
        // meta_data.webhook_secret do nó Evolution + header X-Webhook-Token
        // (ou ?token=) na Evolution. Ausente = vale só o vínculo instância.
        $webhookSecret = $this->tenantWebhookSecret($tenant);

        if (! empty($webhookSecret)) {
            $provided = $request->header('X-Webhook-Token', $request->query('token'));

            if (! is_string($provided) || ! hash_equals((string) $webhookSecret, $provided)) {
                Log::warning('[WhatsappWebhook] Token inválido.', ['tenant_id' => $tenantId]);

                return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
            }
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
                        $this->messenger->updateAck($msgId, (int) $ack, $tenantId);
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
