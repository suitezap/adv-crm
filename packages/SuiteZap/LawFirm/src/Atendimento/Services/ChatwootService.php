<?php

namespace SuiteZap\LawFirm\Atendimento\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * ChatwootService
 *
 * Centralize all HTTP communication with the Chatwoot instance
 * assigned to the current tenant.
 *
 * Configuration is fetched exclusively from the Mothership (Zero-.env rule).
 *
 * Token distinction (CRITICAL):
 *   - api_key (infrastructure_nodes) → Bot/Agent token — used for bot interactions only.
 *     DO NOT use for /labels, /contacts or management APIs (returns HTTP 401).
 *   - access_token (tenants.chatwoot_webhook_token) → User Access Token — required
 *     for all management endpoints (labels, contacts, conversations).
 */
class ChatwootService
{
    protected array $config;

    /** @throws \RuntimeException if no Chatwoot config found for this tenant */
    public function __construct()
    {
        $config = MotherShipService::getChatwootConfig();

        if (empty($config)) {
            throw new \RuntimeException('[ChatwootService] Nenhuma configuração Chatwoot disponível para este tenant.');
        }

        $this->config = $config;
    }

    // =========================================================================
    // Helpers de URL e Headers
    // =========================================================================

    /**
     * Returns the base API URL for the assigned Chatwoot account.
     * Example: https://chat.suitezap.com.br/api/v1/accounts/5
     */
    public function accountUrl(string $path = ''): string
    {
        $accountId = $this->config['account_id'] ?? '';
        $base = rtrim($this->config['base_url'], '/');

        return "{$base}/api/v1/accounts/{$accountId}".($path ? "/{$path}" : '');
    }

    /**
     * Returns HTTP headers using the User Access Token.
     * Required for management operations: labels, contacts, conversations.
     *
     * @see ARCHITECTURE_LawFirm_orient.md §14.4 — Token distinction
     */
    public function managementHeaders(): array
    {
        return [
            'api_access_token' => $this->config['access_token'],
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
        ];
    }

    /**
     * Returns HTTP headers using the Bot Token (api_key from infrastructure_nodes).
     * Safe ONLY for bot message sends and basic conversation queries.
     */
    public function botHeaders(): array
    {
        return [
            'api_access_token' => $this->config['api_key'],
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
        ];
    }

    // =========================================================================
    // Messaging
    // =========================================================================

    /**
     * Sends a text message to a Chatwoot conversation.
     *
     * @param  int  $conversationId  Chatwoot conversation ID
     * @param  string  $message  Text content to send
     * @param  string  $messageType  'outgoing' (default) or 'incoming'
     */
    public function sendMessage(int $conversationId, string $message, string $messageType = 'outgoing'): bool
    {
        try {
            $url = $this->accountUrl("conversations/{$conversationId}/messages");

            $response = Http::timeout(10)
                ->withHeaders($this->botHeaders())
                ->post($url, [
                    'content'      => $message,
                    'message_type' => $messageType,
                    'private'      => false,
                ]);

            if (! $response->successful()) {
                Log::warning('[ChatwootService] sendMessage falhou.', [
                    'conversation_id' => $conversationId,
                    'status'          => $response->status(),
                    'body'            => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] sendMessage exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sends a text message to a Chatwoot conversation from the AI Assistant.
     * Uses assistant_inbox_id if available, otherwise falls back to inbox_id.
     *
     * @param  int  $conversationId  Chatwoot conversation ID
     * @param  string  $message  Text content to send
     */
    public function sendAssistantMessage(int $conversationId, string $message): bool
    {
        try {
            $inboxId = $this->config['assistant_inbox_id'] ?? null;
            if ($inboxId === null) {
                Log::warning('[ChatwootService] assistant_inbox_id não configurado para este tenant — usando inbox_id como fallback.', [
                    'fallback_inbox_id' => $this->config['inbox_id'] ?? null,
                ]);
                $inboxId = $this->config['inbox_id'] ?? null;
            }

            $url = $this->accountUrl("conversations/{$conversationId}/messages");

            $response = Http::timeout(10)
                ->withHeaders($this->botHeaders())
                ->post($url, [
                    'content'      => $message,
                    'message_type' => 'outgoing',
                    'private'      => false,
                    'inbox_id'     => $inboxId,
                ]);

            if (! $response->successful()) {
                Log::warning('[ChatwootService] sendAssistantMessage falhou.', [
                    'conversation_id' => $conversationId,
                    'status'          => $response->status(),
                    'body'            => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] sendAssistantMessage exception: '.$e->getMessage());

            return false;
        }
    }

    // =========================================================================
    // Labels (Management — User Access Token required)
    // =========================================================================

    /**
     * Assigns labels to a conversation.
     * MUST use User Access Token (access_token), NOT bot api_key.
     *
     * @param  array  $labels  e.g. ['trabalhista', 'urgente']
     */
    public function addLabels(int $conversationId, array $labels): bool
    {
        try {
            $url = $this->accountUrl("conversations/{$conversationId}/labels");

            $response = Http::timeout(10)
                ->withHeaders($this->managementHeaders())
                ->post($url, ['labels' => $labels]);

            if (! $response->successful()) {
                Log::warning('[ChatwootService] addLabels falhou.', [
                    'conversation_id' => $conversationId,
                    'labels'          => $labels,
                    'status'          => $response->status(),
                    'body'            => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] addLabels exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Returns the current labels assigned to a conversation.
     */
    public function getLabels(int $conversationId): array
    {
        try {
            $url = $this->accountUrl("conversations/{$conversationId}/labels");

            $response = Http::timeout(10)
                ->withHeaders($this->managementHeaders())
                ->get($url);

            return $response->successful() ? ($response->json('payload') ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] getLabels exception: '.$e->getMessage());

            return [];
        }
    }

    // =========================================================================
    // Contacts (Management — User Access Token required)
    // =========================================================================

    /**
     * Search for a Chatwoot contact by phone number.
     *
     * @param  string  $phone  E.164 format recommended, e.g. "+5511999991234"
     * @return array|null Raw contact payload or null if not found
     */
    public function findContactByPhone(string $phone): ?array
    {
        try {
            $url = $this->accountUrl('contacts/search');

            $response = Http::timeout(10)
                ->withHeaders($this->managementHeaders())
                ->get($url, ['q' => $phone, 'include_contacts' => true]);

            if (! $response->successful()) {
                return null;
            }

            $contacts = $response->json('payload') ?? [];

            return count($contacts) > 0 ? $contacts[0] : null;
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] findContactByPhone exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Create a new contact in Chatwoot.
     *
     * @param  string  $name  Display name
     * @param  string  $phone  Phone number (E.164 recommended)
     * @param  string|null  $email  Optional e-mail
     * @return int|null Chatwoot contact_id on success, null on failure
     */
    public function createContact(string $name, string $phone, ?string $email = null): ?int
    {
        try {
            $url = $this->accountUrl('contacts');

            $payload = array_filter([
                'name'         => $name,
                'phone_number' => $phone,
                'email'        => $email,
                'inbox_id'     => $this->config['inbox_id'],
            ]);

            $response = Http::timeout(10)
                ->withHeaders($this->managementHeaders())
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('[ChatwootService] createContact falhou.', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            return $response->json('id') ?? null;
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] createContact exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Find a Chatwoot contact by phone or create it if not found.
     *
     * @param  string  $phone  E.164 format recommended
     * @param  string  $name  Used only when creating
     * @param  string|null  $email  Used only when creating
     * @return int|null Chatwoot contact_id or null on failure
     */
    public function findOrCreateContact(string $phone, string $name, ?string $email = null): ?int
    {
        $existing = $this->findContactByPhone($phone);

        if ($existing && isset($existing['id'])) {
            return (int) $existing['id'];
        }

        return $this->createContact($name, $phone, $email);
    }

    /**
     * Get all conversations for a given Chatwoot contact.
     *
     * @param  int  $contactId  Chatwoot contact_id
     * @return array Array of conversation objects (may be empty)
     */
    public function getContactConversations(int $contactId): array
    {
        try {
            $url = $this->accountUrl("contacts/{$contactId}/conversations");

            $response = Http::timeout(10)
                ->withHeaders($this->managementHeaders())
                ->get($url);

            if (! $response->successful()) {
                Log::warning('[ChatwootService] getContactConversations falhou.', [
                    'contact_id' => $contactId,
                    'status'     => $response->status(),
                ]);

                return [];
            }

            return $response->json('payload') ?? [];
        } catch (\Throwable $e) {
            Log::error('[ChatwootService] getContactConversations exception: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Sync stage labels on ALL open conversations of a contact.
     *
     * Strategy:
     *   1. Fetch current labels of each open conversation.
     *   2. Strip any label that belongs to the $stagePool (previous stage labels).
     *   3. Append $newStageLabel.
     *   4. PUT the resulting array via addLabels().
     *
     * Non-stage labels (e.g. ORG_WHATS, CLI_PF) are preserved untouched.
     *
     * @param  int  $contactId  Chatwoot contact_id
     * @param  string|array  $newStageLabels  The label(s) to add (e.g. 'LD_NEG' or ['CLI_CONV', 'CAS_NOVO'])
     * @param  array  $stagePool  All possible stage labels for this entity type
     *                            (e.g. ['LD_NOVO','LD_ACOMP','LD_QUAL','LD_NEG','LD_GANHO','LD_PERD'])
     * @return bool True if at least one conversation was updated successfully
     */
    public function syncContactLabels(int $contactId, string|array $newStageLabels, array $stagePool): bool
    {
        $conversations = $this->getContactConversations($contactId);

        if (empty($conversations)) {
            Log::info('[ChatwootService] syncContactLabels: nenhuma conversa encontrada.', [
                'contact_id' => $contactId,
                'labels'     => (array) $newStageLabels,
            ]);

            return false;
        }

        $atLeastOne = false;
        $newLabelsArray = (array) $newStageLabels;

        foreach ($conversations as $conversation) {
            $convId = $conversation['id'] ?? null;
            $status = $conversation['status'] ?? 'resolved';

            // Only update open or pending conversations
            if (! in_array($status, ['open', 'pending'], true) || ! $convId) {
                continue;
            }

            $currentLabels = $this->getLabels((int) $convId);

            // Remove stage pool labels, then add the new one(s)
            $filtered = array_values(array_diff($currentLabels, $stagePool));
            $newLabels = array_values(array_unique(array_merge($filtered, $newLabelsArray)));

            $success = $this->addLabels((int) $convId, array_values($newLabels));

            if ($success) {
                $atLeastOne = true;
                Log::info('[ChatwootService] syncContactLabels: etiquetas sincronizadas.', [
                    'contact_id'      => $contactId,
                    'conversation_id' => $convId,
                    'labels'          => $newLabels,
                ]);
            }
        }

        return $atLeastOne;
    }

    // =========================================================================
    // Config Accessors
    // =========================================================================

    /** Returns the resolved inbox_id for cross-tenant validation in webhooks. */
    public function getInboxId(): ?int
    {
        return isset($this->config['inbox_id']) ? (int) $this->config['inbox_id'] : null;
    }

    /** Returns the access_token used to validate X-Chatwoot-Signature and management API calls. */
    public function getWebhookToken(): ?string
    {
        return $this->config['access_token'] ?? null;
    }
}
