<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\User;

class WhatsappImport extends Model
{
    protected $table = 'law_whatsapp_imports';

    protected $fillable = [
        'processo_id',
        'remote_jid',
        'contact_name',
        'start_date',
        'end_date',
        'message_count',
        'status',
        'imported_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProcessoWhatsappMessage::class, 'import_id');
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Returns a human-readable period string.
     */
    public function formattedPeriod(): string
    {
        $from = $this->start_date ? $this->start_date->format('d/m/Y') : 'Início';
        $to   = $this->end_date   ? $this->end_date->format('d/m/Y')   : 'Atual';
        return "{$from} — {$to}";
    }

    /**
     * Returns a cleaned phone number for display.
     */
    public function displayPhone(): string
    {
        return str_replace('@s.whatsapp.net', '', $this->remote_jid);
    }
}
