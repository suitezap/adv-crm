<?php

namespace SuiteZap\LawFirm\Legal\Repositories;

use Webkul\Core\Eloquent\Repository;
use SuiteZap\LawFirm\Legal\Models\CaseChecklist;

class ChecklistRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return CaseChecklist::class;
    }

    /**
     * Get checklist by Lead ID
     * 
     * @param int $leadId
     * @return CaseChecklist|null
     */
    public function getByLeadId($leadId)
    {
        return $this->findOneWhere(['lead_id' => $leadId]);
    }

    /**
     * Get checklist by Processo ID
     * 
     * @param int $processoId
     * @return CaseChecklist|null
     */
    public function getByProcessoId($processoId)
    {
        return $this->findOneWhere(['processo_id' => $processoId]);
    }
}
