<?php

namespace SuiteZap\LawFirm\Listeners;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\Processo;
use Webkul\Lead\Models\Lead;

class LeadWonListener
{
    /**
     * Handle the lead.update.after event.
     * When a lead is moved to the "won" stage, auto-create a Processo.
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
            Log::warning("LeadWonListener: Lead #{$lead->id} has no person_id, cannot auto-create Processo.");
            return;
        }

        try {
            $processo = Processo::create([
                'titulo' => $lead->title,
                'descricao' => $lead->description,
                'person_id' => $lead->person_id,
                'lead_id' => $lead->id,
                'valor_causa' => $lead->lead_value ?? 0,
                'user_id' => auth()->guard('user')->id() ?? $lead->user_id,
                'status' => 'Ativo',
            ]);

            Log::info("LeadWonListener: Processo #{$processo->id} auto-created for Lead #{$lead->id} (titulo: {$lead->title})");
        } catch (\Exception $e) {
            Log::error("LeadWonListener: Failed to create Processo for Lead #{$lead->id}: " . $e->getMessage());
        }
    }
}
