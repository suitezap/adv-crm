<?php

namespace SuiteZap\LawFirm\Legal\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Webkul\Lead\Models\Lead;
use Webkul\Tag\Models\Tag;

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
     * Maps lead_pipeline_stages.code or name slug → Chatwoot label slug.
     * Compatible with the 6 official lead tags in Chatwoot:
     *   - ld_novo   (Novo)
     *   - ld_acomp  (Acompanhamento)
     *   - ld_qual   (Qualificado)
     *   - ld_neg    (Em negociação)
     *   - ld_ganho  (Ganho)
     *   - ld_perd   (Perdido)
     */
    private const STAGE_LABEL_MAP = [
        'new'            => 'ld_novo',
        'novo'           => 'ld_novo',
        'follow-up'      => 'ld_acomp',
        'follow_up'      => 'ld_acomp',
        'acompanhamento' => 'ld_acomp',
        'prospect'       => 'ld_qual',
        'qualificado'    => 'ld_qual',
        'negotiation'    => 'ld_neg',
        'negociacao'     => 'ld_neg',
        'won'            => 'ld_ganho',
        'ganho'          => 'ld_ganho',
        'lost'           => 'ld_perd',
        'perdido'        => 'ld_perd',
    ];

    /**
     * All Lead stage labels — used to strip previous stage before adding new one.
     * Includes uppercase/legacy variants for thorough cleanup.
     */
    private const LEAD_STAGE_POOL = [
        'ld_novo', 'ld_acomp', 'ld_qual', 'ld_neg', 'ld_ganho', 'ld_perd',
        'LD_NOVO', 'LD_ACOMP', 'LD_QUAL', 'LD_NEG', 'LD_GANHO', 'LD_PERD',
        'Lead', 'lead', 'CLI_CONV', 'CAS_NOVO', 'cli_conv', 'cas_novo',
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

        // ── Resolve All Tags ──────────────────────────────────────────────────
        $lead->load(['stage', 'tags', 'person']);

        $allCrmTagSlugs = $this->resolveAllCrmTagSlugs($lead);
        $crmDynamicPool = $this->getDynamicCrmTagPool();

        // ── Resolve phone from person contacts ────────────────────────────────

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

            $service->syncContactLabels($contactId, $allCrmTagSlugs, $crmDynamicPool);
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

    /**
     * Resolve all Chatwoot labels for the given lead.
     * Includes the stage label (if mapped) and all attached CRM tags.
     */
    private function resolveAllCrmTagSlugs(Lead $lead): array
    {
        $slugs = [];

        // 1. Stage Tag
        if ($lead->stage) {
            $stageCode = strtolower(trim($lead->stage->code ?? ''));
            $stageNameSlug = Str::slug($lead->stage->name ?? '');
            $stageLabel = self::STAGE_LABEL_MAP[$stageCode] ?? self::STAGE_LABEL_MAP[$stageNameSlug] ?? null;

            if ($stageLabel) {
                $slugs[] = $stageLabel;
            }
        }

        // 2. Generic Tags
        if ($lead->tags) {
            foreach ($lead->tags as $tag) {
                // Rule: Previdenciário -> previdenciário (preserves accents)
                $slugs[] = mb_strtolower(trim($tag->name), 'UTF-8');
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * Build the dynamic pool of all CRM tags to control what can be removed from Chatwoot.
     */
    private function getDynamicCrmTagPool(): array
    {
        $pool = [];

        try {
            $tagNames = Tag::pluck('name');
            foreach ($tagNames as $name) {
                $pool[] = mb_strtolower(trim($name), 'UTF-8');
            }
        } catch (\Throwable $e) {
            Log::warning('[SyncLeadStageToChatwootListener] Erro ao carregar pool de tags: '.$e->getMessage());
        }

        // Always merge the hardcoded stage pool for safety/backwards compatibility
        $pool = array_merge($pool, self::LEAD_STAGE_POOL);

        return array_values(array_unique($pool));
    }
}
