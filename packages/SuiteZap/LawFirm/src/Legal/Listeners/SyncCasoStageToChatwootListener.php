<?php

namespace SuiteZap\LawFirm\Legal\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;
use SuiteZap\LawFirm\Legal\Events\CasoStageUpdated;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * SyncCasoStageToChatwootListener
 *
 * Listens to CasoStageUpdated and syncs the Caso person's
 * Chatwoot contact labels to the new stage label.
 *
 * Golden Rules (ARCHITECTURE_LawFirm_orient.md §6 & §14.5):
 *   - NEVER throws — always degrades gracefully.
 *   - If Chatwoot is not configured for this tenant: log + return.
 *   - Uses ShouldQueue to avoid blocking the HTTP request.
 */
class SyncCasoStageToChatwootListener implements ShouldQueue
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
     * Maps normalized LegalPipelineStage name slug → Chatwoot label slug.
     * Based on tag_mapping_documentation.md §4 (Casos — Tons Profundos).
     *
     * Keys are generated via Str::slug($stage->name) for locale-independence.
     */
    private const STAGE_LABEL_MAP = [
        'novo-caso'              => 'CAS_NOVO',
        'novo'                   => 'CAS_NOVO',
        'em-analise'             => 'CAS_ANAL',
        'analise'                => 'CAS_ANAL',
        'aguardando-cliente'     => 'CAS_AGCLI',
        'ag-cliente'             => 'CAS_AGCLI',
        'producao-interna'       => 'CAS_PROD',
        'producao'               => 'CAS_PROD',
        'protocolado'            => 'CAS_PROT',
        'protocolo'              => 'CAS_PROT',
        'aguardando-judiciario'  => 'CAS_AGJUD',
        'ag-judiciario'          => 'CAS_AGJUD',
        'prazo-em-andamento'     => 'CAS_PRAZO',
        'prazo'                  => 'CAS_PRAZO',
        'audiencia'              => 'CAS_AUD',
        'audiencia-designada'    => 'CAS_AUD',
        'sentenca'               => 'CAS_SENT',
        'sentenca-proferida'     => 'CAS_SENT',
        'recurso'                => 'CAS_RECUR',
        'em-grau-de-recurso'     => 'CAS_RECUR',
        'execucao'               => 'CAS_EXEC',
        'cumprimento-de-sentenca'=> 'CAS_EXEC',
        'encerrado'              => 'CAS_ENCER',
    ];

    /**
     * All Caso stage labels — used to strip previous stage before adding new one.
     */
    private const CASO_STAGE_POOL = [
        'CAS_NOVO', 'CAS_ANAL', 'CAS_AGCLI', 'CAS_PROD',
        'CAS_PROT', 'CAS_AGJUD', 'CAS_PRAZO', 'CAS_AUD',
        'CAS_SENT', 'CAS_RECUR', 'CAS_EXEC', 'CAS_ENCER',
    ];

    /**
     * Handle the CasoStageUpdated event.
     */
    public function handle(CasoStageUpdated $event): void
    {
        $caso = $event->caso;

        // ── Guard: Chatwoot configured for this tenant? ───────────────────────
        $chatwootConfig = MotherShipService::getChatwootConfig();
        if (empty($chatwootConfig)) {
            Log::info('[SyncCasoStageToChatwootListener] Chatwoot não configurado para este tenant. Ignorando.');

            return;
        }

        // ── Resolve stage label ───────────────────────────────────────────────
        $caso->loadMissing('stage');

        if (! $caso->stage) {
            return;
        }

        $stageName = $caso->stage->name ?? $caso->status ?? '';
        $stageSlug = Str::slug($stageName);
        $stageLabel = self::STAGE_LABEL_MAP[$stageSlug] ?? null;

        if (! $stageLabel) {
            Log::info('[SyncCasoStageToChatwootListener] Stage sem mapeamento Chatwoot.', [
                'caso_id'    => $caso->id,
                'stage_name' => $stageName,
                'stage_slug' => $stageSlug,
            ]);

            return;
        }

        // ── Resolve phone from person contacts ────────────────────────────────
        $caso->loadMissing('person');

        $phone = $this->resolvePhone($caso);

        if (! $phone) {
            Log::warning('[SyncCasoStageToChatwootListener] Caso sem telefone — impossível sincronizar.', [
                'caso_id' => $caso->id,
            ]);

            return;
        }

        // ── ChatwootService: find or create contact, then sync labels ─────────
        try {
            $service = new ChatwootService;
            $name = $caso->person?->name ?? "Caso #{$caso->id}";
            $email = is_array($caso->person?->emails) ? ($caso->person->emails[0]['value'] ?? null) : null;
            $contactId = $service->findOrCreateContact($phone, $name, $email);

            if (! $contactId) {
                Log::warning('[SyncCasoStageToChatwootListener] Contato não encontrado/criado no Chatwoot.', [
                    'caso_id' => $caso->id,
                    'phone'   => $phone,
                ]);

                return;
            }

            $service->syncContactLabels($contactId, $stageLabel, self::CASO_STAGE_POOL);
        } catch (\RuntimeException $e) {
            Log::warning('[SyncCasoStageToChatwootListener] ChatwootService indisponível: '.$e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[SyncCasoStageToChatwootListener] Erro inesperado: '.$e->getMessage(), [
                'caso_id' => $caso->id,
            ]);
        }
    }

    /**
     * Extract the first phone number from the caso's person.
     * Person stores contact numbers in the JSON column 'contact_numbers',
     * which is cast to array: [['value' => '11999998888', 'label' => 'phone'], ...]
     */
    private function resolvePhone(Caso $caso): ?string
    {
        $person = $caso->person;

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
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) >= 10 && ! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return '+'.$digits;
    }
}
