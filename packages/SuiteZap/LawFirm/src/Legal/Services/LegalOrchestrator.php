<?php

namespace SuiteZap\LawFirm\Legal\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\AI\Models\LeadTriagem;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\Processo;
use Webkul\Lead\Models\Lead;

/**
 * LegalOrchestrator — Domain Service (v3.46)
 *
 * Coordinates multi-entity operations within the Legal domain.
 * Ensures transactional atomicity when creating Caso → Processo hierarchies.
 * Enriches Caso with area/prioridade from LeadTriagem AI data.
 *
 * Golden Rule: All creation logic lives HERE, not in Controllers or Listeners.
 */
class LegalOrchestrator
{
    protected CasoService $casoService;

    /**
     * Canonical status pipeline shared by both Caso and Processo.
     * Order represents the typical life-cycle progression.
     * Used in all <select> dropdowns, DataGrid badges and business guards.
     */
    public const VALID_STATUSES = [
        'Novo Caso',
        'Em Análise',
        'Aguardando Cliente',
        'Em Produção Jurídica',
        'Protocolado',
        'Aguardando Judiciário',
        'Prazo/Ação Necessária',
        'Audiência',
        'Sentença',
        'Recurso',
        'Execução',
        'Encerrado',
    ];

    /**
     * Valid areas de direito (canonical list).
     */
    private const VALID_AREAS = [
        'Administrativo', 'Ambiental', 'Bancário', 'Consumidor', 'Cível',
        'Digital / LGPD', 'Empresarial', 'Família', 'Imobiliário',
        'Penal', 'Previdenciário', 'Trabalhista', 'Tributário',
    ];

    /**
     * Urgency → Priority mapping.
     * The triagem AI may use different urgency labels; this normalizes them.
     */
    private const URGENCIA_TO_PRIORIDADE = [
        'baixa'       => 'Baixa',
        'baixo'       => 'Baixa',
        'media'       => 'Média',
        'média'       => 'Média',
        'medio'       => 'Média',
        'médio'       => 'Média',
        'moderada'    => 'Média',
        'moderado'    => 'Média',
        'alta'        => 'Alta',
        'alto'        => 'Alta',
        'elevada'     => 'Alta',
        'elevado'     => 'Alta',
        'urgente'     => 'Alta',
        'critica'     => 'Crítica',
        'crítica'     => 'Crítica',
        'critico'     => 'Crítica',
        'crítico'     => 'Crítica',
        'emergencial' => 'Crítica',
    ];

    public function __construct(CasoService $casoService)
    {
        $this->casoService = $casoService;
    }

    /**
     * Convert a WON Lead into the full legal structure: Caso + Processo.
     * Enriches Caso with area/prioridade from LeadTriagem when available.
     *
     * @param  Lead  $lead  The lead that was moved to the WON stage.
     * @return array{caso: Caso, processo: Processo}
     *
     * @throws \Throwable Re-thrown after logging on transaction failure.
     */
    public function convertLeadToLegalStructure(Lead $lead): array
    {
        return DB::transaction(function () use ($lead) {
            // Extract enrichment data from LeadTriagem (AI triage)
            $triagem = LeadTriagem::where('lead_id', $lead->id)->latest()->first();

            $area = $this->resolveArea($lead, $triagem);
            $prioridade = $this->resolvePrioridade($lead, $triagem);

            Log::info("LegalOrchestrator: Triagem for Lead #{$lead->id} → area={$area}, prioridade={$prioridade}");

            // 1. Create the parent Caso (enriched with triagem data)
            $caso = $this->casoService->createCaso([
                'titulo'          => $lead->title,
                'descricao'       => $lead->description,
                'person_id'       => $lead->person_id,
                'user_id'         => auth()->guard('user')->id() ?? $lead->user_id,
                'status'          => 'Novo Caso',
                'area'            => $area,
                'prioridade'      => $prioridade,
            ]);

            // 2. Create the child Processo linked to the Caso
            $processo = Processo::create([
                'titulo'      => $lead->title,
                'descricao'   => $lead->description,
                'person_id'   => $lead->person_id,
                'lead_id'     => $lead->id,
                'caso_id'     => $caso->id,
                'valor_causa' => $lead->lead_value ?? 0,
                'user_id'     => $caso->user_id,
                'status'      => 'Em Análise',
            ]);

            Log::info("LegalOrchestrator: Caso #{$caso->id} + Processo #{$processo->id} created for Lead #{$lead->id}");

            return compact('caso', 'processo');
        });
    }

    /**
     * Resolve the area de direito from Lead Tags or triagem data.
     * Uses fuzzy matching against the canonical list of valid areas.
     */
    private function resolveArea(Lead $lead, ?LeadTriagem $triagem): string
    {
        // 1. Check Lead tags first (human truth)
        foreach ($lead->tags as $tag) {
            $tagName = trim($tag->name);
            foreach (self::VALID_AREAS as $validArea) {
                if (mb_strtolower($tagName) === mb_strtolower($validArea) || mb_stripos($tagName, $validArea) !== false) {
                    return $validArea;
                }
            }
        }

        // 2. Fallback to LeadTriagem
        if (! $triagem || empty($triagem->area)) {
            return 'Cível'; // Safe default
        }

        $rawArea = trim($triagem->area);

        // Exact match first
        foreach (self::VALID_AREAS as $validArea) {
            if (mb_strtolower($rawArea) === mb_strtolower($validArea)) {
                return $validArea;
            }
        }

        // Partial/contains match (e.g., "Direito Trabalhista" → "Trabalhista")
        foreach (self::VALID_AREAS as $validArea) {
            if (mb_stripos($rawArea, $validArea) !== false || mb_stripos($validArea, $rawArea) !== false) {
                return $validArea;
            }
        }

        // Special cases for AI variations
        $lowerArea = mb_strtolower($rawArea);
        if (str_contains($lowerArea, 'lgpd') || str_contains($lowerArea, 'digital') || str_contains($lowerArea, 'tecnologia')) {
            return 'Digital / LGPD';
        }
        if (str_contains($lowerArea, 'trabalh')) {
            return 'Trabalhista';
        }
        if (str_contains($lowerArea, 'previden')) {
            return 'Previdenciário';
        }
        if (str_contains($lowerArea, 'tribut') || str_contains($lowerArea, 'fiscal')) {
            return 'Tributário';
        }
        if (str_contains($lowerArea, 'consum')) {
            return 'Consumidor';
        }
        if (str_contains($lowerArea, 'famil') || str_contains($lowerArea, 'divórcio') || str_contains($lowerArea, 'guarda')) {
            return 'Família';
        }
        if (str_contains($lowerArea, 'imobil') || str_contains($lowerArea, 'locaç')) {
            return 'Imobiliário';
        }
        if (str_contains($lowerArea, 'empres') || str_contains($lowerArea, 'societá')) {
            return 'Empresarial';
        }
        if (str_contains($lowerArea, 'ambient')) {
            return 'Ambiental';
        }
        if (str_contains($lowerArea, 'bancá') || str_contains($lowerArea, 'financ')) {
            return 'Bancário';
        }
        if (str_contains($lowerArea, 'admin')) {
            return 'Administrativo';
        }
        if (str_contains($lowerArea, 'penal') || str_contains($lowerArea, 'criminal')) {
            return 'Penal';
        }

        // Fallback: use the raw value if it exists, otherwise default
        Log::warning("LegalOrchestrator: Unknown area '{$rawArea}' from triagem, defaulting to 'Cível'");

        return 'Cível';
    }

    /**
     * Resolve prioridade from Lead Tags or triagem urgencia field.
     * Maps labels to the 4-level priority scale.
     */
    private function resolvePrioridade(Lead $lead, ?LeadTriagem $triagem): string
    {
        $validPrioridades = ['Baixa', 'Média', 'Alta', 'Crítica'];

        // 1. Check Lead tags first (human truth)
        foreach ($lead->tags as $tag) {
            $tagName = mb_strtolower(trim($tag->name));

            // Direct mapping
            if (isset(self::URGENCIA_TO_PRIORIDADE[$tagName])) {
                return self::URGENCIA_TO_PRIORIDADE[$tagName];
            }

            // Valid string check
            foreach ($validPrioridades as $valid) {
                if ($tagName === mb_strtolower($valid)) {
                    return $valid;
                }
            }
        }

        // 2. Fallback to LeadTriagem
        if (! $triagem || empty($triagem->urgencia)) {
            return 'Baixa'; // Safe default
        }

        $rawUrgencia = mb_strtolower(trim($triagem->urgencia));

        // Direct mapping
        if (isset(self::URGENCIA_TO_PRIORIDADE[$rawUrgencia])) {
            return self::URGENCIA_TO_PRIORIDADE[$rawUrgencia];
        }

        // Partial match fallback
        foreach (self::URGENCIA_TO_PRIORIDADE as $key => $value) {
            if (str_contains($rawUrgencia, $key) || str_contains($key, $rawUrgencia)) {
                return $value;
            }
        }

        // If the triagem value already matches a valid prioridade, use it
        foreach ($validPrioridades as $valid) {
            if (mb_strtolower($rawUrgencia) === mb_strtolower($valid)) {
                return $valid;
            }
        }

        Log::warning("LegalOrchestrator: Unknown urgencia '{$triagem->urgencia}' from triagem, defaulting to 'Baixa'");

        return 'Baixa';
    }
}
