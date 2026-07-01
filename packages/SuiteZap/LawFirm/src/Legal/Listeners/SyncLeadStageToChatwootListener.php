<?php

namespace SuiteZap\LawFirm\Legal\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Webkul\Lead\Models\Lead;

/**
 * SyncLeadStageToChatwootListener
 *
 * Listens to the Krayin 'lead.update.after' event and syncs
 * the corresponding Chatwoot contact's stage label.
 *
 * Golden Rules (ARCHITECTURE_LawFirm_orient.md §6 & §14.5):
 *   - NEVER throws — always degrades gracefully.
 *   - If Chatwoot is not configured for this tenant: log + return.
 *   - Uses ShouldQueue to avoid blocking the HTTP request.
 */
class SyncLeadStageToChatwootListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'default';

    /**
     * Do not retry on failure — avoid flooding Chatwoot API.
     */
    public int $tries = 1;

    /**
     * Maps lead_pipeline_stages.code → Chatwoot label slug.
     * Based on tag_mapping_documentation.md §3 (Leads — Tons Frios).
     */
    private const STAGE_LABEL_MAP = [
        'new'         => 'LD_NOVO',
        'follow-up'   => 'LD_ACOMP',
        'prospect'    => 'LD_QUAL',
        'negotiation' => 'LD_NEG',
        'won'         => ['CLI_CONV', 'CAS_NOVO'],
        'lost'        => 'LD_PERD',
    ];

    /**
     * All Lead stage labels — used to strip previous stage before adding new one.
     */
    private const LEAD_STAGE_POOL = [
        'LD_NOVO', 'LD_ACOMP', 'LD_QUAL', 'LD_NEG', 'LD_GANHO', 'LD_PERD',
        'CLI_CONV', 'CAS_NOVO',
    ];

    /**
     * Handle the 'lead.update.after' event.
     *
     * @param  Lead|mixed  $lead
     */
    public function handle(mixed $lead): void
    {
        // ── Guard: accept only Lead Eloquent models ───────────────────────────
        if (! $lead instanceof Lead) {
            return;
        }

        // ── Guard: Chatwoot configured for this tenant? ───────────────────────
        $chatwootConfig = MotherShipService::getChatwootConfig();
        if (empty($chatwootConfig)) {
            // Graceful degradation — Chatwoot not set up for this tenant.
            Log::info('[SyncLeadStageToChatwootListener] Chatwoot não configurado para este tenant. Ignorando.');

            return;
        }

        // ── Resolve stage ─────────────────────────────────────────────────────
        $lead->loadMissing('stage');

        if (! $lead->stage) {
            return;
        }

        $stageCode = strtolower($lead->stage->code ?? '');
        $stageLabel = self::STAGE_LABEL_MAP[$stageCode] ?? null;

        if (! $stageLabel) {
            // Stage code not in map — skip silently.
            return;
        }

        // ── Resolve phone from person contacts ────────────────────────────────
        $lead->loadMissing('person');

        $phone = $this->resolvePhone($lead);

        if (! $phone) {
            Log::warning('[SyncLeadStageToChatwootListener] Lead sem telefone — impossível sincronizar.', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        // ── ChatwootService: find or create contact, then sync labels ─────────
        try {
            $service = new ChatwootService;
            $name = $lead->person?->name ?? $lead->title ?? "Lead #{$lead->id}";
            $email = is_array($lead->person?->emails) ? ($lead->person->emails[0]['value'] ?? null) : null;
            $contactId = $service->findOrCreateContact($phone, $name, $email);

            if (! $contactId) {
                Log::warning('[SyncLeadStageToChatwootListener] Contato não encontrado/criado no Chatwoot.', [
                    'lead_id' => $lead->id,
                    'phone'   => $phone,
                ]);

                return;
            }

            $service->syncContactLabels($contactId, $stageLabel, self::LEAD_STAGE_POOL);
        } catch (\RuntimeException $e) {
            // ChatwootService constructor throws RuntimeException if config is empty.
            // This is an extra safety net — getChatwootConfig() check above should prevent this.
            Log::warning('[SyncLeadStageToChatwootListener] ChatwootService indisponível: '.$e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[SyncLeadStageToChatwootListener] Erro inesperado: '.$e->getMessage(), [
                'lead_id' => $lead->id,
            ]);
        }
    }

    /**
     * Extract the first mobile/phone number from the lead's person.
     * Person stores contact numbers in the JSON column 'contact_numbers',
     * which is cast to array: [['value' => '11999998888', 'label' => 'phone'], ...]
     */
    private function resolvePhone(Lead $lead): ?string
    {
        $person = $lead->person;

        if (! $person) {
            return null;
        }

        // contact_numbers is a JSON column cast to array in the Person model.
        $contactNumbers = $person->contact_numbers ?? [];

        foreach ($contactNumbers as $contact) {
            $value = $contact['value'] ?? null;
            if ($value) {
                return $this->normalizePhone((string) $value);
            }
        }

        return null;
    }

    /**
     * Normalize phone to E.164 format (Brazilian default: add +55 if missing).
     */
    private function normalizePhone(string $phone): string
    {
        // Strip non-numeric chars
        $digits = preg_replace('/\D/', '', $phone);

        // Brazil: add country code if missing
        if (strlen($digits) >= 10 && ! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return '+'.$digits;
    }
}
