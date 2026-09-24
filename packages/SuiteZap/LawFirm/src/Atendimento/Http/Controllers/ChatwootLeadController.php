<?php

namespace SuiteZap\LawFirm\Atendimento\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
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
    public function __construct() {}

    /**
     * Resolve the ChatwootService gracefully.
     * Throws RuntimeException if not configured, which we catch in actions.
     */
    protected function getChatwootService(): ChatwootService
    {
        return app(ChatwootService::class);
    }

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
            $chatwoot = $this->getChatwootService();

            // Fetch all messages — Chatwoot returns them newest-first.
            // We pass page=1 (most recent page) without a "before" cursor so we
            // always get the latest batch, not the oldest. The Accept header
            // avoids the occasional 406 in some Chatwoot builds.
            $queryParams = $request->only(['limit', 'since', 'before', 'page']);
            $url = $chatwoot->accountUrl("conversations/{$conversationId}/messages");
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }
            $response = Http::timeout(15)
                ->withHeaders(array_merge($chatwoot->managementHeaders(), [
                    'Cache-Control' => 'no-cache, no-store',
                    'Pragma'        => 'no-cache',
                    'Accept'        => 'application/json',
                ]))
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

            // Sort oldest-first by created_at regardless of what Chatwoot returns.
            // Some Chatwoot versions return newest-first, others oldest-first.
            // We sort explicitly so the JS can always scroll to bottom = newest.
            usort($messages, fn ($a, $b) => ($a['created_at'] ?? 0) <=> ($b['created_at'] ?? 0));


            return response()->json([
                'conversation_id' => $conversationId,
                'messages'        => $messages,
            ])->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma'        => 'no-cache',
                'Expires'       => '0',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
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
        \Log::debug('[ChatwootLeadController] send raw request', [
            'all' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'has_attachments' => $request->hasFile('attachments')
        ]);

        $request->validate([
            'message' => 'nullable|string|max:4096',
            'private' => 'sometimes',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:30720', // 30MB max
        ]);

        $messageContent = $request->input('message', '');
        $hasAttachments = $request->hasFile('attachments');

        if (empty($messageContent) && ! $hasAttachments) {
            return response()->json(['error' => 'A mensagem ou anexo são obrigatórios.'], 400);
        }

        $conversationId = $lead->chatwoot_conversation_id ? (int) $lead->chatwoot_conversation_id : null;
        if (! $conversationId) {
            return response()->json(['error' => 'Nenhuma conversa Chatwoot vinculada a este Lead.'], 404);
        }

        try {
            $chatwoot = $this->getChatwootService();
            $url = $chatwoot->accountUrl("conversations/{$conversationId}/messages");
            
            $headers = $chatwoot->managementHeaders();
            if ($hasAttachments) {
                // Remove application/json so Guzzle can set multipart/form-data with the correct boundary
                unset($headers['Content-Type']);
            }

            $req = Http::timeout(60)->withHeaders($headers);

            if ($hasAttachments) {
                foreach ($request->file('attachments') as $file) {
                    $req->attach('attachments[]', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
                }
            }

            // Chatwoot requires 'true' or 'false' string for multipart boolean
            $isPrivate = filter_var($request->input('private', false), FILTER_VALIDATE_BOOLEAN);

            $response = $req->post($url, [
                'content'      => (string) $messageContent,
                'message_type' => 'outgoing',
                'private'      => $isPrivate ? 'true' : 'false',
            ]);

            // Log full response to diagnose inbox routing issue
            Log::debug('[ChatwootLeadController] send response', [
                'lead_id'              => $lead->id,
                'target_conversation'  => $conversationId,
                'http_status'          => $response->status(),
                'response_body'        => $response->json(),
            ]);

            if (! $response->successful()) {
                return response()->json(['error' => 'Erro ao enviar mensagem: ' . $response->body()], $response->status());
            }

            return response()->json($response->json());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
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
            $chatwoot = $this->getChatwootService();
            $url = $chatwoot->accountUrl('canned_responses');
            $params = [];
            if ($q = $request->query('q')) {
                $params['search'] = $q;
            }

            $response = Http::timeout(10)
                ->withHeaders($chatwoot->managementHeaders())
                ->get($url, $params);

            if (! $response->successful()) {
                return response()->json(['canned_responses' => []]);
            }

            return response()->json(['canned_responses' => $response->json() ?? []]);
        } catch (\RuntimeException $e) {
            return response()->json(['canned_responses' => []]);
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
            $chatwoot = $this->getChatwootService();
            $url = $chatwoot->accountUrl('macros');
            $response = Http::timeout(10)
                ->withHeaders($chatwoot->managementHeaders())
                ->get($url, ['page' => 1]);

            if (! $response->successful()) {
                return response()->json(['macros' => []]);
            }

            $data = $response->json();
            $macros = $data['payload'] ?? $data ?? [];

            return response()->json(['macros' => $macros]);
        } catch (\RuntimeException $e) {
            return response()->json(['macros' => []]);
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
            $chatwoot = $this->getChatwootService();
            $url = $chatwoot->accountUrl("macros/{$macroId}/run");
            $response = Http::timeout(15)
                ->withHeaders($chatwoot->managementHeaders())
                ->post($url, ['conversation_id' => $conversationId]);

            if (! $response->successful()) {
                return response()->json(['error' => 'Erro ao executar macro.'], $response->status());
            }

            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] runMacro exception: '.$e->getMessage());

            return response()->json(['error' => 'Erro interno ao executar macro.'], 500);
        }
    }

    /**
     * Transcribe an audio attachment via OpenAI Whisper and post a private note.
     */
    public function transcribeAudio(Request $request, Lead $lead, $messageId): JsonResponse
    {
        $conversationId = $lead->chatwoot_conversation_id ? (int) $lead->chatwoot_conversation_id : null;
        if (! $conversationId) {
            return response()->json(['error' => 'Nenhuma conversa vinculada.'], 404);
        }

        $audioUrl = $request->input('attachment_url');
        if (! $audioUrl) {
            return response()->json(['error' => 'URL do áudio não fornecida.'], 400);
        }

        $apiKey = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getAppConfig('openai_api_key');
        if (! $apiKey) {
            return response()->json(['error' => 'Chave da API da OpenAI não configurada no MotherShip (app_config).'], 500);
        }

        try {
            $chatwoot = $this->getChatwootService();
            
            // 1. Baixar o áudio temporariamente
            $audioContent = Http::timeout(20)->get($audioUrl);
            if (! $audioContent->successful()) {
                return response()->json(['error' => 'Falha ao baixar o áudio do Chatwoot.'], 500);
            }

            $tmpDir = storage_path('app/temp');
            if (! is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }
            $tmpPath = $tmpDir . '/audio_' . time() . '_' . uniqid() . '.mp3';
            file_put_contents($tmpPath, $audioContent->body());

            // 2. Enviar para a API Whisper da OpenAI
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->attach('file', file_get_contents($tmpPath), 'audio.mp3')
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'pt',
                ]);

            // Limpeza do arquivo
            @unlink($tmpPath);

            if (! $response->successful()) {
                Log::error('[ChatwootLeadController] OpenAI error: ' . $response->body());
                return response()->json(['error' => 'Falha na transcrição via OpenAI.'], 500);
            }

            $transcription = $response->json('text');
            if (empty($transcription)) {
                return response()->json(['error' => 'Áudio vazio ou inaudível.'], 400);
            }

            // 3. Postar nota privada no Chatwoot
            $noteText = "📝 **Transcrição (Áudio)**:\n" . $transcription;
            
            $url = $chatwoot->accountUrl("conversations/{$conversationId}/messages");
            $postResponse = Http::timeout(15)
                ->withHeaders($chatwoot->managementHeaders())
                ->post($url, [
                    'content' => $noteText,
                    'message_type' => 'outgoing',
                    'private' => true,
                ]);

            if (! $postResponse->successful()) {
                return response()->json(['error' => 'Transcrito, mas falha ao salvar nota privada.'], 500);
            }

            return response()->json(['success' => true, 'text' => $transcription]);

        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] transcribe exception: '.$e->getMessage());
            return response()->json(['error' => 'Erro interno ao transcrever áudio.'], 500);
        }
    }

    /**
     * Update the lead's pipeline stage from the chat modal.
     */
    public function updateStage(Request $request, Lead $lead): JsonResponse
    {
        $stageId = $request->input('stage_id');
        if (! $stageId) {
            return response()->json(['error' => 'ID da etapa não fornecido.'], 400);
        }

        try {
            $leadRepository = app(\Webkul\Lead\Repositories\LeadRepository::class);

            // Mirror Krayin native controller: dispatch events so all listeners fire
            // (including SyncLeadStageToChatwootListener)
            Event::dispatch('lead.update.before', $lead->id);

            $updatedLead = $leadRepository->update([
                'lead_pipeline_stage_id' => $stageId,
                'entity_type'            => 'leads',
            ], $lead->id);

            Event::dispatch('lead.update.after', $updatedLead);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('[ChatwootLeadController] updateStage exception: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar a etapa.'], 500);
        }
    }
}

