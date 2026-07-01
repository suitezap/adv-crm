<?php

namespace SuiteZap\LawFirm\Legal\Repositories;

use SuiteZap\LawFirm\Legal\Models\CaseChecklist;
use Webkul\Core\Eloquent\Repository;

class ChecklistRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return CaseChecklist::class;
    }

    /**
     * Get checklist by Lead ID
     *
     * @param  int  $leadId
     * @return CaseChecklist|null
     */
    public function getByLeadId($leadId)
    {
        return $this->findOneWhere(['lead_id' => $leadId]);
    }

    /**
     * Get checklist by Processo ID
     *
     * @param  int  $processoId
     * @return CaseChecklist|null
     */
    public function getByProcessoId($processoId)
    {
        return $this->findOneWhere(['processo_id' => $processoId]);
    }
}
