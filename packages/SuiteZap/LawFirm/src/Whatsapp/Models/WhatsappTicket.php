<?php

namespace SuiteZap\LawFirm\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTicket extends Model
{
    protected $table = 'law_whatsapp_tickets';

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'user_id',
        'status',
        'last_message_id',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function contact()
    {
        return $this->belongsTo(WhatsappContact::class, 'contact_id');
    }

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class, 'ticket_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(WhatsappMessage::class, 'ticket_id')->latest();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
