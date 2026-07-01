<?php

namespace SuiteZap\LawFirm\Whatsapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Whatsapp\Models\WhatsappTicket;
use SuiteZap\LawFirm\Whatsapp\Services\MessengerService;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $tenantId,
        public int $ticketId,
        public string $text
    ) {}

    public function handle(MessengerService $messenger): void
    {
        // Guard: always eager-load contact to avoid N+1 and null-pointer in sendText
        $ticket = WhatsappTicket::with('contact')
            ->where('tenant_id', $this->tenantId) // tenant isolation guard
            ->find($this->ticketId);

        if (! $ticket) {
            Log::warning("[SendWhatsappMessageJob] Ticket {$this->ticketId} not found for tenant {$this->tenantId}.");

            return;
        }

        if ($ticket->status === 'closed') {
            Log::warning("[SendWhatsappMessageJob] Ticket {$this->ticketId} is closed — aborting send.");

            return;
        }

        $message = $messenger->sendText($this->tenantId, $ticket, $this->text);

        if (! $message) {
            Log::error("[SendWhatsappMessageJob] Failed to send message for ticket {$this->ticketId}");
        }
    }
}
