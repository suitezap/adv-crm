<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalPipelineStage extends Model
{
    protected $table = 'law_legal_pipeline_stages';

    protected $fillable = [
        'pipeline_id',
        'name',
        'code',
        'sort_order',
        'color',
    ];

    /**
     * Get the pipeline this stage belongs to.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(LegalPipeline::class, 'pipeline_id');
    }

    /**
     * Get the casos currently at this stage.
     */
    public function casos(): HasMany
    {
        return $this->hasMany(Caso::class, 'legal_pipeline_stage_id');
    }
}
