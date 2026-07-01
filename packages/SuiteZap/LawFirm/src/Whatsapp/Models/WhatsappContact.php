<?php

namespace SuiteZap\LawFirm\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    protected $table = 'law_whatsapp_contacts';

    protected $fillable = [
        'tenant_id',
        'phone',
        'name',
        'email',
        'person_id',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function tickets()
    {
        return $this->hasMany(WhatsappTicket::class, 'contact_id');
    }

    public function openTicket()
    {
        return $this->hasOne(WhatsappTicket::class, 'contact_id')
            ->whereIn('status', ['pending', 'open']);
    }
}
