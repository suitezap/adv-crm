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
     * Get a secure signed URL for the file.
     * Retorna rota de proxy interno para evitar "SignatureDoesNotMatch" no MinIO com Reverse Proxy.
     * (Storage::temporaryUrl() removido — usa route() via proxy do backend, Regra 2.2)
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
            return 'pdf-icon'; // Adjust based on your icon set usage
        } elseif (str_contains($mime, 'image')) {
            return 'image-icon';
        } elseif (str_contains($mime, 'word') || str_contains($mime, 'document')) {
            return 'word-icon';
        }

        return 'file-icon';
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
