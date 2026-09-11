<?php

namespace SuiteZap\LawFirm\Atendimento\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Jobs\ProcessChatwootConversationCreatedJob;
use SuiteZap\LawFirm\Atendimento\Jobs\ProcessChatwootMessageCreatedJob;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * ChatwootWebhookController
 *
 * Receives and validates incoming webhook events from the Chatwoot instance
 * assigned to this tenant.
 *
 * Security rules (ARCHITECTURE_LawFirm_orient.md §15):
 *   1. Validate X-Chatwoot-Signature using HMAC-SHA1 with the tenant's webhook_token.
 *   2. Validate inbox_id to prevent cross-tenant event leakage.
 *   3. Return HTTP 200 IMMEDIATELY and delegate processing to Laravel Jobs.
 *      Never process synchronously to avoid Chatwoot's 15-second webhook timeout.
 *
 * Route: POST /api/webhooks/chatwoot  (CSRF-exempt, 'api' middleware)
 */
class ChatwootWebhookController extends Controller
{
    /**
     * Handle an incoming Chatwoot webhook event.
     */
    public function handle(Request $request)
    {
        // ── Step 1: Load tenant Chatwoot config from Mothership ──
        $config = MotherShipService::getChatwootConfig();

        if (empty($config) || empty($config['access_token'])) {
            Log::warning('[ChatwootWebhook] Tenant sem configuração Chatwoot. Ignorando evento.');

            // Still return 200 to avoid Chatwoot disabling the webhook on repeated failures.
            return response()->json(['status' => 'ignored', 'reason' => 'no_config'], 200);
        }

        // ── Step 2: Validate HMAC-SHA1 signature ──
        $signature = $request->header('X-Chatwoot-Signature');
        $rawBody = $request->getContent();
        $expected = 'sha1='.hash_hmac('sha1', $rawBody, $config['access_token']);

        if (! hash_equals($expected, (string) $signature)) {
            Log::warning('[ChatwootWebhook] Assinatura inválida — possível requisição não autorizada.', [
                'ip'       => $request->ip(),
                'received' => $signature,
            ]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        // ── Step 3: Validate inbox_id (cross-tenant guard) ──
        $inboxId = $request->input('inbox.id');
        $expectedInbox = $config['inbox_id'] ?? null;

        if ($expectedInbox && (int) $inboxId !== (int) $expectedInbox) {
            Log::warning('[ChatwootWebhook] inbox_id inválido — evento descartado.', [
                'received' => $inboxId,
                'expected' => $expectedInbox,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'inbox_mismatch'], 200);
        }

        // ── Step 4: Return 200 immediately (prevent Chatwoot 15s timeout) ──
        // All processing must be dispatched to Laravel queued Jobs below.

        $event = $request->input('event');
        $conversationId = $request->input('id');
        $payload = $request->all();

        Log::info('[ChatwootWebhook] Evento recebido.', [
            'event'           => $event,
            'conversation_id' => $conversationId,
        ]);

        // ── Step 5: Dispatch processing Jobs by event type ──
        match ($event) {
            'conversation_created' => $this->handleConversationCreated($payload),
            'message_created'      => $this->handleMessageCreated($payload),
            default                => null, // Unknown events are silently ignored
        };

        return response()->json(['status' => 'ok'], 200);
    }

    // =========================================================================
    // Event Handlers — dispatch to queued Jobs
    // =========================================================================

    /**
     * Handles the 'conversation_created' event.
     * Future: dispatch a Job to auto-tag, link to Lead, etc.
     */
    protected function handleConversationCreated(array $payload): void
    {
        ProcessChatwootConversationCreatedJob::dispatch($payload);

        Log::info('[ChatwootWebhook] conversation_created — Job despachado.', [
            'conversation_id' => $payload['id'] ?? null,
        ]);
    }

    /**
     * Handles the 'message_created' event.
     * Future: dispatch a Job to forward message, create task, etc.
     */
    protected function handleMessageCreated(array $payload): void
    {
        ProcessChatwootMessageCreatedJob::dispatch($payload);

        Log::info('[ChatwootWebhook] message_created — Job despachado.', [
            'conversation_id' => $payload['conversation']['id'] ?? null,
        ]);
    }
}
