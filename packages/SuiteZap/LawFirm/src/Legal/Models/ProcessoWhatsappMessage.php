<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ProcessoWhatsappMessage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'law_processo_whatsapp_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'processo_id',
        'import_id',
        'remote_jid',
        'sender_name',
        'message_text',
        'message_id',
        'message_timestamp',
        'is_from_me',
        'payload',
        'media_url',
        'media_type',
        'anexo_id',
        'media_source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'message_timestamp' => 'datetime',
        'is_from_me'        => 'boolean',
        'payload'           => 'array',
    ];

    /**
     * Get the processo that owns the message.
     */
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    /**
     * Get the import session this message belongs to.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(WhatsappImport::class, 'import_id');
    }

    /**
     * Get the GED Anexo associated with the downloaded media (if any).
     */
    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class, 'anexo_id');
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::deleting(function ($msg) {
            if ($msg->anexo_id) {
                try {
                    $anexo = Anexo::find($msg->anexo_id);
                    if ($anexo) {
                        $anexo->delete(); // Dispara o evento deleting do Anexo para apagar do S3
                    }
                } catch (\Throwable $e) {
                    Log::error('Erro ao deletar Anexo via evento deleting de ProcessoWhatsappMessage: '.$e->getMessage());
                }
            }
        });
    }
}
