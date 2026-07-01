<?php

namespace SuiteZap\LawFirm\Legal\Events;

use SuiteZap\LawFirm\Legal\Models\Caso;

/**
 * CasoStageUpdated
 *
 * Dispatched by LegalPipelineService::moveCaseToStage() whenever
 * a Caso is moved to a new stage on the Legal Kanban board.
 *
 * Listeners may use this event to trigger side-effects such as
 * syncing Chatwoot labels (SyncCasoStageToChatwootListener).
 */
class CasoStageUpdated
{
    /**
     * @param  Caso  $caso  The updated Caso model (with stage already refreshed).
     */
    public function __construct(
        public readonly Caso $caso
    ) {}
}
