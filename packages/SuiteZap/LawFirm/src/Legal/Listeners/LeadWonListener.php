<?php

namespace SuiteZap\LawFirm\Legal\Listeners;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Services\LegalOrchestrator;
use Webkul\Lead\Models\Lead;

class LeadWonListener
{
    protected LegalOrchestrator $orchestrator;

    public function __construct(LegalOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Handle the lead.update.after event.
     * When a lead is moved to the "won" stage, auto-create a Caso + Processo.
     *
     * @param  Lead  $lead
     * @return void
     */
    public function handle($lead)
    {
        if (!$lead instanceof Lead) {
            return;
        }

        // Load the stage relationship if not loaded
        $lead->loadMissing('stage');

        if (!$lead->stage || $lead->stage->code !== 'won') {
            return;
        }

        // Prevent duplicate: check if a Processo already exists for this lead
        $exists = Processo::where('lead_id', $lead->id)->exists();

        if ($exists) {
            Log::info("LeadWonListener: Processo already exists for lead #{$lead->id}, skipping.");
            return;
        }

        // Ensure lead has a person_id (required by Processo)
        if (!$lead->person_id) {
            Log::warning("LeadWonListener: Lead #{$lead->id} has no person_id, cannot auto-create Caso/Processo.");
            return;
        }

        try {
            $result = $this->orchestrator->convertLeadToLegalStructure($lead);

            Log::info("LeadWonListener: Caso #{$result['caso']->id} + Processo #{$result['processo']->id} auto-created for Lead #{$lead->id}");
        } catch (\Exception $e) {
            Log::error("LeadWonListener: Failed to create Caso/Processo for Lead #{$lead->id}: " . $e->getMessage());
        }
    }
}

