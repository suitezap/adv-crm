<?php

namespace SuiteZap\LawFirm\Models;

use Illuminate\Database\Eloquent\Model;
use Krayin\CRM\Models\Lead;

class AiExecution extends Model
{
    protected $table = 'lawfirm_ai_executions';

    protected $guarded = [];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'confidence' => 'decimal:2',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function humanDecision()
    {
        return $this->hasOne(HumanDecision::class, 'execution_id');
    }
}
