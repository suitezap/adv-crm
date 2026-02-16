<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Anexo extends Model
{
    protected $table = 'law_processo_anexos';

    protected $fillable = [
        'processo_id',
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
     * Uses Storage::temporaryUrl() for private bucket access with 15min expiration.
     */
    /**
     * Get a secure signed URL for the file.
     * Uses Storage::temporaryUrl() for private bucket access with 15min expiration.
     */
    public function getUrlAttribute(): string
    {
        $path = $this->path;

        if (empty($path)) {
            return '';
        }

        // Remove 'public/' from start of path if present (legacy storage)
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        // Use temporaryUrl for secure signed URLs (valid for 15 minutes)
        try {
            // Force S3 disk as configured by MotherShipService
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
        } catch (\Exception $e) {
            // Fallback to regular URL if temporaryUrl fails
            try {
                return Storage::disk('s3')->url($path);
            } catch (\Exception $e2) {
                return '';
            }
        }
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
