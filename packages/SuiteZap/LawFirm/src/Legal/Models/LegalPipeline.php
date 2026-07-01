<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalPipeline extends Model
{
    protected $table = 'law_legal_pipelines';

    protected $fillable = ['name'];

    /**
     * Get the stages that belong to this pipeline.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(LegalPipelineStage::class, 'pipeline_id')->orderBy('sort_order');
    }
}
