<?php

namespace SuiteZap\LawFirm\Atendimento\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;
use Webkul\Lead\Models\Lead;

/**
 * ChatwootLeadController
 *
 * Serves as an internal proxy between the CRM lead view and the Chatwoot API.
 * Route model binding resolves {lead} → Lead model automatically.
 * All routes require the standard 'user' middleware (authenticated CRM users).
 *
 * Endpoints:
 *   GET  /admin/juridico/atendimento/leads/{lead}/chatwoot/messages
 *   POST /admin/juridico/atendimento/leads/{lead}/chatwoot/send
 *   GET  /admin/juridico/atendimento/leads/{lead}/chatwoot/canned-responses
 *   GET  /admin/juridico/atendimento/leads/{lead}/chatwoot/macros
 *   POST /admin/juridico/atendimento/leads/{lead}/chatwoot/macros/{macroId}/run
 */
class ChatwootLeadController extends Controller
{
    public function __construct(
        protected ChatwootService $chatwoot,
    ) {}

    // =========================================================================
    // Messages
    // =========================================================================

    /**
     * Return paginated messages for the lead's Chatwoot conversation.
     */
    public function messages(Request $request, Lead $lead): JsonResponse
    {
        $conversationId = $lead->chatwoot_conversation_id ? (int) $lead->chatwoot_conversation_id : null;
        if (! $conversationId) {
            return response()->json(['error' => 'Nenhuma conversa Chatwoot vinculada a este Lead.'], 404);
        }

        try {
            $url = $this->chatwoot->accountUrl("conversations/{$conversationId}/messages");
            $response = Http::timeout(15)
                ->withHeaders($this->chatwoot->managementHeaders())
                ->get($url);

            if (! $response->successful()) {
                return response()->json(['error' => 'Erro ao buscar mensagens no Chatwoot.'], $response->status());
            }

            $raw = $response->json();

            // Log raw response shape for debugging (keys only, not values)
            Log::debug('[ChatwootLeadController] messages raw keys', [
                'conversation_id' => $conversationId,
                'top_keys'        => is_array($raw) ? array_keys($raw) : gettype($raw),
            ]);

            /*
             * Chatwoot API response shapes vary by version:
             *   v2/v3: { "data": { "meta": {...}, "payload": [ ...messages ] } }
             *   older: { "payload": { "messages": [...], "meta": {...} } }
             *   older: { "payload": [ ...messages ] }
             *   some:  [ ...messages ]  (bare array)
             */
            $messages = match (true) {
                // v2/v3: root key is "data" with nested "payload"
                isset($raw['data']['payload']) && is_array($raw['data']['payload']) => $raw['data']['payload'],

                // payload is array directly (flat list of messages)
                isset($raw['payload']) && is_array($raw['payload']) && array_is_list($raw['payload']) => $raw['payload'],

                // payload is object with 'messages' key
                isset($raw['payload']['messages']) && is_array($raw['payload']['messages']) => $raw['payload']['messages'],

                // bare array response
                is_array($raw) && array_is_list($raw) => $raw,

                default => [],
            };

            // Oldest-first (Chatwoot returns newest-first)
            $messages = array_reverse($messages);

            return response()->json([
                'conversation_id' => $conversationId,
                'messages'        => $messages,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] messages exception: '.$e->getMessage());

            return response()->json(['error' => 'Erro interno ao buscar mensagens.'], 500);
        }
    }

    // =========================================================================
    // Send Message
    // =========================================================================

    /**
     * Send a reply to the lead's Chatwoot conversation.
     */
    public function send(Request $request, Lead $lead): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:4096',
            'private' => 'sometimes|boolean',
        ]);

        $conversationId = $lead->chatwoot_conversation_id ? (int) $lead->chatwoot_conversation_id : null;
        if (! $conversationId) {
            return response()->json(['error' => 'Nenhuma conversa Chatwoot vinculada a este Lead.'], 404);
        }

        try {
            $url = $this->chatwoot->accountUrl("conversations/{$conversationId}/messages");
            $response = Http::timeout(15)
                ->withHeaders($this->chatwoot->managementHeaders())
                ->post($url, [
                    'content'      => $request->input('message'),
                    'message_type' => 'outgoing',
                    'private'      => (bool) $request->input('private', false),
                ]);

            // Log full response to diagnose inbox routing issue
            Log::debug('[ChatwootLeadController] send response', [
                'lead_id'              => $lead->id,
                'target_conversation'  => $conversationId,
                'http_status'          => $response->status(),
                'response_conv_id'     => $response->json('conversation_id') ?? $response->json('id'),
                'response_inbox_id'    => $response->json('inbox_id'),
                'response_keys'        => array_keys($response->json() ?? []),
            ]);

            if (! $response->successful()) {
                return response()->json(['error' => 'Erro ao enviar mensagem.'], $response->status());
            }

            return response()->json($response->json());
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] send exception: '.$e->getMessage());

            return response()->json(['error' => 'Erro interno ao enviar mensagem.'], 500);
        }
    }

    // =========================================================================
    // Canned Responses
    // =========================================================================

    /**
     * Return the account's canned responses (optionally filtered by keyword).
     */
    public function cannedResponses(Request $request, Lead $lead): JsonResponse
    {
        try {
            $url = $this->chatwoot->accountUrl('canned_responses');
            $params = [];
            if ($q = $request->query('q')) {
                $params['search'] = $q;
            }

            $response = Http::timeout(10)
                ->withHeaders($this->chatwoot->managementHeaders())
                ->get($url, $params);

            if (! $response->successful()) {
                return response()->json(['canned_responses' => []]);
            }

            return response()->json(['canned_responses' => $response->json() ?? []]);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] cannedResponses exception: '.$e->getMessage());

            return response()->json(['canned_responses' => []]);
        }
    }

    // =========================================================================
    // Macros
    // =========================================================================

    /**
     * Return the account's macros list.
     */
    public function macros(Request $request, Lead $lead): JsonResponse
    {
        try {
            $url = $this->chatwoot->accountUrl('macros');
            $response = Http::timeout(10)
                ->withHeaders($this->chatwoot->managementHeaders())
                ->get($url, ['page' => 1]);

            if (! $response->successful()) {
                return response()->json(['macros' => []]);
            }

            $data = $response->json();
            $macros = $data['payload'] ?? $data ?? [];

            return response()->json(['macros' => $macros]);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] macros exception: '.$e->getMessage());

            return response()->json(['macros' => []]);
        }
    }

    /**
     * Run a macro on the lead's conversation.
     */
    public function runMacro(Request $request, Lead $lead, int $macroId): JsonResponse
    {
        $conversationId = $lead->chatwoot_conversation_id ? (int) $lead->chatwoot_conversation_id : null;
        if (! $conversationId) {
            return response()->json(['error' => 'Nenhuma conversa Chatwoot vinculada a este Lead.'], 404);
        }

        try {
            $url = $this->chatwoot->accountUrl("macros/{$macroId}/run");
            $response = Http::timeout(15)
                ->withHeaders($this->chatwoot->managementHeaders())
                ->post($url, ['conversation_id' => $conversationId]);

            if (! $response->successful()) {
                return response()->json(['error' => 'Erro ao executar macro.'], $response->status());
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] runMacro exception: '.$e->getMessage());

            return response()->json(['error' => 'Erro interno ao executar macro.'], 500);
        }
    }
}
