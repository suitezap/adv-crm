<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class DocumentTemplate extends Model
{
    protected $table = 'law_document_templates';

    protected $fillable = [
        'titulo',
        'tipo',
        'area_direito',
        'conteudo',
        'descricao',
        'ativo',
        'is_layout',
        'user_id',
    ];

    protected $casts = [
        'ativo'     => 'boolean',
        'is_layout' => 'boolean',
    ];

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Returns a unique string identifier for this template in the format "local-{id}".
     * Used to distinguish local templates from global ones (which use "global-{id}").
     */
    public function getUniqueIdAttribute(): string
    {
        return 'local-'.$this->id;
    }

    /**
     * Always returns false — this template lives in the local tenant DB.
     */
    public function getIsGlobalAttribute(): bool
    {
        return false;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include active templates.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope a query to include templates for a specific area,
     * plus templates that are available for all areas (area_direito is null).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForArea($query, ?string $area)
    {
        return $query->where(function ($q) use ($area) {
            $q->whereNull('area_direito');

            if (! empty($area)) {
                $q->orWhere('area_direito', $area);
            }
        });
    }

    /**
     * Get the user that created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
