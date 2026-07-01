<?php

namespace SuiteZap\LawFirm\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $table = 'law_whatsapp_messages';

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'evolution_message_id',
        'from_me',
        'type',
        'body',
        'ack',
    ];

    protected $casts = [
        'body'    => 'array',
        'from_me' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(WhatsappTicket::class, 'ticket_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getText(): string
    {
        return $this->body['text'] ?? $this->body['caption'] ?? '[mídia]';
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'audio', 'video', 'document']);
    }
}
