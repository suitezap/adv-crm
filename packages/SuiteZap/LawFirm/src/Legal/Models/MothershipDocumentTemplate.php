<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a document template stored in the central Mothership database.
 * These are read-only "global" templates available to all tenants on the platform.
 *
 * Connection: 'mothership' (central DB, configured via zero-.env MotherShipService)
 * Table: lawfirm_document_templates
 */
class MothershipDocumentTemplate extends Model
{
    protected $connection = 'mothership';

    protected $table = 'lawfirm_document_templates';

    /**
     * Global templates are managed centrally and cannot be modified per-tenant.
     */
    public $timestamps = true;

    protected $fillable = [
        'titulo',
        'tipo',
        'area_direito',
        'conteudo',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Returns a unique string identifier for this template in the format "global-{id}".
     * Used to distinguish global templates from local ones (which use "local-{id}").
     */
    public function getUniqueIdAttribute(): string
    {
        return 'global-'.$this->id;
    }

    /**
     * Always returns true — this template lives in the mothership DB.
     */
    public function getIsGlobalAttribute(): bool
    {
        return true;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope a query to include templates for a specific area,
     * plus templates that are available for all areas (area_direito is null).
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
}
