<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anexo extends Model
{
    protected $table = 'law_processo_anexos';

    protected $fillable = [
        'processo_id',
        'caso_id',
        'path',
        'nome_original',
        'tipo_mime',
        'tamanho',
    ];

    /**
     * Get the processo that owns the attachment.
     */
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    /**
     * Get a secure URL for the file via the internal proxy backend route.
     * Usa proxy do backend para evitar "SignatureDoesNotMatch" em ambientes MinIO com Reverse Proxy.
     * Conforme Regra 2.2 (SKILL.md): acesso a arquivos via SaasFileService, nunca direto.
     */
    public function getUrlAttribute(): string
    {
        $path = $this->path;

        if (empty($path)) {
            return '';
        }

        // Retorna a rota do proxy interno do Laravel para evitar o erro
        // "SignatureDoesNotMatch" que ocorre quando o Reverse Proxy no servidor MinIO
        // não avança o cabeçalho 'Host' original. O backend resolve e autentica via SDK.
        return route('admin.processos.download_attachment', $this->id);
    }

    /**
     * Get a FontAwesome icon class based on file type.
     */
    public function getIconAttribute(): string
    {
        $mime = $this->tipo_mime ?? '';

        if (str_contains($mime, 'pdf')) {
            return 'icon-file';
        } elseif (str_contains($mime, 'image')) {
            return 'icon-image';
        } elseif (str_contains($mime, 'word') || str_contains($mime, 'document')) {
            return 'icon-file';
        }

        return 'icon-file';
    }

    public function getExtensionAttribute()
    {
        $name = $this->nome_original ?? '';
        if (empty($name)) {
            return '';
        }

        return strtoupper(pathinfo($name, PATHINFO_EXTENSION));
    }
}
