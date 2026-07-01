<?php

namespace SuiteZap\LawFirm\Whatsapp\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Models\WhatsappContact;
use SuiteZap\LawFirm\Whatsapp\Models\WhatsappMessage;
use SuiteZap\LawFirm\Whatsapp\Models\WhatsappTicket;

class MessengerService
{
    // ── Upsert Logic (Webhook Ingestion) ─────────────────────────────────────

    /**
     * Process a single raw message payload from the Evolution "messages.upsert" event.
     * Idempotent: repeated calls with the same evolution_message_id are safe.
     */
    public function processIncoming(int $tenantId, array $rawMessage): ?WhatsappMessage
    {
        $key = $rawMessage['key'] ?? [];
        $msgId = $key['id'] ?? null;
        $remoteJid = $key['remoteJid'] ?? null;
        $fromMe = (bool) ($key['fromMe'] ?? false);

        if (! $remoteJid || ! $msgId) {
            return null;
        }

        // Strip @s.whatsapp.net suffix; skip group messages
        $phone = preg_replace('/@.+/', '', $remoteJid);
        if (str_contains($remoteJid, '@g.us')) {
            return null;
        }

        // ── Contact (find or create) ─────────────────────────────────────────
        $contact = WhatsappContact::firstOrCreate(
            ['tenant_id' => $tenantId, 'phone' => $phone],
            [
                'name'      => $rawMessage['pushName'] ?? $phone,
                'person_id' => $this->findKrayinPersonId($tenantId, $phone),
            ]
        );

        // ── Ticket (find open/pending or create new pending) ─────────────────
        $ticket = WhatsappTicket::where('tenant_id', $tenantId)
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['pending', 'open'])
            ->latest()
            ->first();

        if (! $ticket) {
            $ticket = WhatsappTicket::create([
                'tenant_id'  => $tenantId,
                'contact_id' => $contact->id,
                'status'     => 'pending',
            ]);
        }

        // ── Message (upsert — idempotent via evolution_message_id) ───────────
        $body = $this->extractBody($rawMessage);
        $type = $this->detectType($rawMessage);

        $message = WhatsappMessage::updateOrCreate(
            ['evolution_message_id' => $msgId],
            [
                'tenant_id' => $tenantId,
                'ticket_id' => $ticket->id,
                'from_me'   => $fromMe,
                'type'      => $type,
                'body'      => $body,
                'ack'       => $fromMe ? 1 : 0,
            ]
        );

        // Update last_message_id on ticket
        $ticket->update(['last_message_id' => $message->id]);

        return $message;
    }

    /**
     * Update ACK (delivery status) for a message. Called from messages.update webhook.
     */
    public function updateAck(string $evolutionMessageId, int $ack): void
    {
        WhatsappMessage::where('evolution_message_id', $evolutionMessageId)
            ->update(['ack' => $ack]);
    }

    // ── Outgoing ─────────────────────────────────────────────────────────────

    /**
     * Send a text message via Evolution API.
     * Credentials are fetched from MotherShip (never from .env).
     */
    public function sendText(int $tenantId, WhatsappTicket $ticket, string $text): ?WhatsappMessage
    {
        $config = MotherShipService::getEvolutionConfig($tenantId);

        if (! $config || empty($config['base_url']) || empty($config['token'])) {
            Log::error("[MessengerService] Evolution API not configured for tenant {$tenantId}");

            return null;
        }

        // Guard: contact must be loaded to get phone number
        if (! $ticket->relationLoaded('contact')) {
            $ticket->load('contact');
        }

        if (! $ticket->contact) {
            Log::error("[MessengerService] Ticket {$ticket->id} has no contact.");

            return null;
        }

        $phone = $ticket->contact->phone;
        $instance = $config['instance'];

        try {
            $client = new Client([
                'base_uri' => rtrim($config['base_url'], '/'),
                'headers'  => [
                    'apikey'       => $config['token'],
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ]);

            $response = $client->post("/message/sendText/{$instance}", [
                'json' => [
                    'number'      => $phone,
                    'text'        => $text,
                    'delay'       => 1200,
                    'linkPreview' => false,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $msgId = $data['key']['id'] ?? null;

            $msg = WhatsappMessage::create([
                'tenant_id'            => $tenantId,
                'ticket_id'            => $ticket->id,
                'evolution_message_id' => $msgId,
                'from_me'              => true,
                'type'                 => 'text',
                'body'                 => ['text' => $text],
                'ack'                  => 1,
            ]);

            $ticket->update(['last_message_id' => $msg->id]);

            return $msg;

        } catch (\Exception $e) {
            Log::error('[MessengerService] sendText error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Send a media message (image, audio, video, document) via Evolution API.
     */
    public function sendMedia(int $tenantId, WhatsappTicket $ticket, string $mediaUrl, string $type, ?string $caption = null): ?WhatsappMessage
    {
        $config = MotherShipService::getEvolutionConfig($tenantId);

        if (! $config || empty($config['base_url']) || empty($config['token'])) {
            Log::error("[MessengerService] Evolution API not configured for tenant {$tenantId}");

            return null;
        }

        if (! $ticket->relationLoaded('contact')) {
            $ticket->load('contact');
        }

        if (! $ticket->contact) {
            Log::error("[MessengerService] Ticket {$ticket->id} has no contact for sendMedia.");

            return null;
        }

        $phone = $ticket->contact->phone;
        $instance = $config['instance'];

        try {
            $client = new Client([
                'base_uri' => rtrim($config['base_url'], '/'),
                'headers'  => [
                    'apikey'       => $config['token'],
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 60,
            ]);

            $payload = [
                'number'    => $phone,
                'media'     => $mediaUrl,
                'mediatype' => $type,
                'delay'     => 1200,
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            $response = $client->post("/message/sendMedia/{$instance}", [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody(), true);
            $msgId = $data['key']['id'] ?? null;

            $msg = WhatsappMessage::create([
                'tenant_id'            => $tenantId,
                'ticket_id'            => $ticket->id,
                'evolution_message_id' => $msgId,
                'from_me'              => true,
                'type'                 => $type,
                'body'                 => [
                    'text'     => $caption,
                    'mediaUrl' => $mediaUrl,
                ],
                'ack' => 1,
            ]);

            $ticket->update(['last_message_id' => $msg->id]);

            return $msg;

        } catch (\Exception $e) {
            Log::error('[MessengerService] sendMedia error: '.$e->getMessage());

            return null;
        }
    }

    // ── Accept / Close ────────────────────────────────────────────────────────

    public function acceptTicket(WhatsappTicket $ticket, int $userId): void
    {
        $ticket->update(['status' => 'open', 'user_id' => $userId]);
    }

    public function closeTicket(WhatsappTicket $ticket, int $tenantId, ?string $farewellMessage = null): void
    {
        if ($farewellMessage) {
            $this->sendText($tenantId, $ticket, $farewellMessage);
        }

        $ticket->update(['status' => 'closed']);
    }

    /**
     * Manually find or create a ticket for a phone number.
     * Used to initiate conversations from the CRM.
     *
     * BUG FIX: `status` must NOT be in the search key array for firstOrCreate,
     * otherwise it would create a new ticket every time status changes.
     * Instead: find any active open ticket or create a new one.
     */
    public function getOrCreateTicket(int $tenantId, string $phone): WhatsappTicket
    {
        $phone = preg_replace('/\D/', '', $phone);

        $contact = WhatsappContact::firstOrCreate(
            ['tenant_id' => $tenantId, 'phone' => $phone],
            [
                'name'      => $phone,
                'person_id' => $this->findKrayinPersonId($tenantId, $phone),
            ]
        );

        // Find an existing open ticket first
        $ticket = WhatsappTicket::where('tenant_id', $tenantId)
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['pending', 'open'])
            ->latest()
            ->first();

        // Only create if none exists
        if (! $ticket) {
            $ticket = WhatsappTicket::create([
                'tenant_id'  => $tenantId,
                'contact_id' => $contact->id,
                'status'     => 'open',
                'user_id'    => Auth::id(),
            ]);
        }

        return $ticket;
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function findKrayinPersonId(int $tenantId, string $phone): ?int
    {
        try {
            return DB::table('persons')
                ->where('contact_numbers->0->value', 'LIKE', "%{$phone}%")
                ->value('id');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractBody(array $rawMessage): array
    {
        $msg = $rawMessage['message'] ?? [];

        if (isset($msg['conversation'])) {
            return ['text' => $msg['conversation']];
        }
        if (isset($msg['extendedTextMessage']['text'])) {
            return ['text' => $msg['extendedTextMessage']['text']];
        }
        foreach (['imageMessage', 'audioMessage', 'videoMessage', 'documentMessage'] as $key) {
            if (isset($msg[$key])) {
                return [
                    'text'     => $msg[$key]['caption'] ?? null,
                    'mediaUrl' => $msg[$key]['url'] ?? null,
                    'mime'     => $msg[$key]['mimetype'] ?? null,
                    'filename' => $msg[$key]['fileName'] ?? null,
                ];
            }
        }

        return ['text' => null];
    }

    private function detectType(array $rawMessage): string
    {
        $msg = $rawMessage['message'] ?? [];
        if (isset($msg['imageMessage'])) {
            return 'image';
        }
        if (isset($msg['audioMessage'])) {
            return 'audio';
        }
        if (isset($msg['videoMessage'])) {
            return 'video';
        }
        if (isset($msg['documentMessage'])) {
            return 'document';
        }

        return 'text';
    }
}
