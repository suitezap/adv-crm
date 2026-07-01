<?php

namespace SuiteZap\LawFirm\Legal\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Events\CasoStageUpdated;
use SuiteZap\LawFirm\Legal\Jobs\SyncProcessoStatusJob;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\LegalPipelineStage;
use SuiteZap\LawFirm\Legal\Models\Processo;

/**
 * LegalPipelineService — Orchestrates Kanban stage transitions.
 *
 * When a Caso moves between pipeline stages:
 * 1. Updates the Caso's status and FK atomically.
 * 2. Cascade-updates all child Processos' status.
 * 3. For large volumes (>10 processos), dispatches async Job.
 */
class LegalPipelineService
{
    /**
     * Threshold for dispatching cascade update via Queue instead of inline.
     */
    private const ASYNC_THRESHOLD = 10;

    /**
     * Move a Caso to a new pipeline stage, cascading status to child Processos.
     *
     * @param  Caso  $caso  The case to transition.
     * @param  int  $stageId  Target LegalPipelineStage ID.
     * @return Caso The updated Caso instance.
     *
     * @throws \InvalidArgumentException If stage does not exist.
     * @throws \Throwable Re-thrown on transaction failure.
     */
    public function moveCaseToStage(Caso $caso, int $stageId): Caso
    {
        $stage = LegalPipelineStage::find($stageId);

        if (! $stage) {
            throw new \InvalidArgumentException("Pipeline stage #{$stageId} not found.");
        }

        $processosCount = Processo::where('caso_id', $caso->id)->count();

        // Capture the result so we can dispatch the event after the transaction commits.
        $updatedCaso = DB::transaction(function () use ($caso, $stage, $processosCount) {
            // 1. Update the Caso itself
            $caso->update([
                'legal_pipeline_stage_id' => $stage->id,
                'status'                  => $stage->name,
            ]);

            $caso->refresh();

            // 2. Cascade status to child Processos
            if ($processosCount > 0 && $processosCount <= self::ASYNC_THRESHOLD) {
                // Inline batch update (fast, within the same transaction)
                Processo::where('caso_id', $caso->id)
                    ->update(['status' => $stage->name]);

                Log::info("LegalPipelineService: Caso #{$caso->id} → stage '{$stage->name}' — {$processosCount} processos updated inline.");
            } elseif ($processosCount > self::ASYNC_THRESHOLD) {
                // Async: dispatch job after transaction commits
                SyncProcessoStatusJob::dispatch($caso->id, $stage->name);

                Log::info("LegalPipelineService: Caso #{$caso->id} → stage '{$stage->name}' — {$processosCount} processos queued for async update.");
            }

            return $caso;
        });

        // Dispatch AFTER the transaction commits so listeners see the persisted state.
        // Queued listeners (SyncCasoStageToChatwootListener) will not block this HTTP response.
        Event::dispatch(new CasoStageUpdated($updatedCaso));

        return $updatedCaso;
    }
}
