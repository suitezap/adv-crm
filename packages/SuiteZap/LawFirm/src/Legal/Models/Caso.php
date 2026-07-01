<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caso extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'law_casos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'titulo',
        'area',
        'status',
        'legal_pipeline_stage_id',
        'prioridade',
        'descricao',
        'user_id',
        'person_id',
        'organization_id',
    ];

    // ─── Relationships ──────────────────────────────────────

    /**
     * Get the pipeline stage this caso is currently at.
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(LegalPipelineStage::class, 'legal_pipeline_stage_id');
    }

    /**
     * Get the processos linked to this caso.
     */
    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }

    /**
     * Get the responsible lawyer (user).
     */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(\Webkul\User\Models\User::class, 'user_id');
    }

    /**
     * Get the person (client PF) associated with the caso.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Contact\Models\Person::class);
    }

    /**
     * Get the organization (client PJ) associated with the caso.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Contact\Models\Organization::class);
    }

    // ─── Computed Attributes ────────────────────────────────

    /**
     * Get total revenue aggregated from child processos.
     */
    public function getReceitaTotalAttribute(): float
    {
        return $this->processos->sum(function ($processo) {
            return $processo->receita_total;
        });
    }

    /**
     * Get total expenses aggregated from child processos.
     */
    public function getDespesasTotaisAttribute(): float
    {
        return $this->processos->sum(function ($processo) {
            return $processo->despesas_totais;
        });
    }

    /**
     * Get net profit aggregated from child processos.
     */
    public function getLucroLiquidoAttribute(): float
    {
        return $this->receita_total - $this->despesas_totais;
    }

    /**
     * Get count of linked processos.
     */
    public function getProcessosCountAttribute(): int
    {
        return $this->processos()->count();
    }

    /**
     * Get CSS badge class for status (covers all 12 pipeline stages).
     */
    public function getStatusBadgeClassAttribute(): string
    {
        // Prefer pipeline stage color when linked
        if ($this->stage && $this->stage->color) {
            return $this->stage->color;
        }

        return 'bg-gray-100 text-gray-600';
    }

    /**
     * Get human-readable status label (passthrough — values are already in PT-BR).
     */
    public function getStatusLabelAttribute(): string
    {
        // Prefer pipeline stage name when linked
        if ($this->stage) {
            return $this->stage->name;
        }

        return $this->status ?? 'Sem Status';
    }
}
