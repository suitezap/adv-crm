<?php

namespace SuiteZap\LawFirm\Legal\Services;

use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\LegalPipelineStage;
use SuiteZap\LawFirm\Legal\Repositories\CasoRepository;

class CasoService
{
    protected CasoRepository $casoRepository;

    protected LegalPipelineService $pipelineService;

    public function __construct(
        CasoRepository $casoRepository,
        LegalPipelineService $pipelineService
    ) {
        $this->casoRepository = $casoRepository;
        $this->pipelineService = $pipelineService;
    }

    /**
     * Create a new caso.
     */
    public function createCaso(array $data): Caso
    {
        $data['person_id'] = ! empty($data['person_id']) ? $data['person_id'] : null;
        $data['organization_id'] = ! empty($data['organization_id']) ? $data['organization_id'] : null;
        $data['user_id'] = ! empty($data['user_id']) ? $data['user_id'] : null;

        return $this->casoRepository->create($data);
    }

    /**
     * Update an existing caso.
     * When status changes, also syncs legal_pipeline_stage_id so the Kanban column is correct.
     */
    public function updateCaso(array $data, int $id): Caso
    {
        $data['person_id'] = ! empty($data['person_id']) ? $data['person_id'] : null;
        $data['organization_id'] = ! empty($data['organization_id']) ? $data['organization_id'] : null;
        $data['user_id'] = ! empty($data['user_id']) ? $data['user_id'] : null;

        $caso = $this->casoRepository->update($data, $id);

        // Keep Kanban column in sync with the status field.
        // Look up the stage whose name matches the new status string.
        if (! empty($data['status'])) {
            $stage = LegalPipelineStage::where('name', $data['status'])->first();

            if ($stage && $caso->legal_pipeline_stage_id !== $stage->id) {
                $caso->update(['legal_pipeline_stage_id' => $stage->id]);
                $caso->refresh();
            }
        }

        return $caso;
    }

    /**
     * Get aggregated KPIs for a caso (from child processos).
     */
    public function getKPIs(Caso $caso): array
    {
        $caso->load('processos.financeiros');

        return [
            'processos_count'  => $caso->processos->count(),
            'receita_total'    => $caso->receita_total,
            'despesas_totais'  => $caso->despesas_totais,
            'lucro_liquido'    => $caso->lucro_liquido,
        ];
    }
}
