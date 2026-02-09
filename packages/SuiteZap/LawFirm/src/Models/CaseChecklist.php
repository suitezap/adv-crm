<?php

namespace SuiteZap\LawFirm\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Models\Lead;

class CaseChecklist extends Model
{
    protected $table = 'lawfirm_case_checklists';

    protected $fillable = [
        'lead_id',
        'type',
        'current_step',
        'step_data',
        'ai_last_feedback',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'step_data' => 'array',
        'ai_last_feedback' => 'array',
    ];

    /**
     * Obtém o Lead associado a este checklist.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Helper para atualizar dados de uma etapa específica sem apagar as outras.
     */
    public function updateStepData(int $step, array $data)
    {
        $currentData = $this->step_data ?? [];
        $currentData[$step] = $data;

        $this->update(['step_data' => $currentData]);
    }
}
