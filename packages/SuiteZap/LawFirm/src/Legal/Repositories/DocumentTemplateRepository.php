<?php

namespace SuiteZap\LawFirm\Legal\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\DocumentTemplate;
use SuiteZap\LawFirm\Legal\Models\MothershipDocumentTemplate;
use SuiteZap\LawFirm\Legal\Models\Processo;

class DocumentTemplateRepository
{
    // =========================================================================
    // Public API (Used by controllers & views)
    // =========================================================================

    /**
     * Get all active document templates (local + global).
     * Global templates from the Mothership DB are merged after local ones.
     */
    public function allActive(): Collection
    {
        $local = DocumentTemplate::active()->where('is_layout', false)->orderBy('titulo')->get();
        $global = $this->fetchGlobalTemplates();

        return $local->concat($global);
    }

    /**
     * Get active document templates relevant for a specific process based on its area.
     * Merges local + global templates, filtered by the processo's area.
     */
    public function forProcesso(Processo $processo): Collection
    {
        $local = DocumentTemplate::active()
            ->where('is_layout', false)
            ->forArea($processo->area_direito)
            ->orderBy('titulo')
            ->get();

        $global = $this->fetchGlobalTemplates($processo->area_direito);

        return $local->concat($global);
    }

    /**
     * Find a document template by its unique_id string (e.g. "local-5" or "global-3").
     * Returns either a DocumentTemplate or a MothershipDocumentTemplate instance.
     *
     * @return DocumentTemplate|MothershipDocumentTemplate
     *
     * @throws ModelNotFoundException
     */
    public function findByUniqueId(string $uniqueId)
    {
        [$source, $id] = $this->parseUniqueId($uniqueId);

        if ($source === 'global') {
            return MothershipDocumentTemplate::findOrFail((int) $id);
        }

        return DocumentTemplate::findOrFail((int) $id);
    }

    /**
     * Backward-compatible alias: find a LOCAL template by its integer ID.
     * Deprecated in favour of findByUniqueId(). Use only for legacy call-sites.
     */
    public function find(int $id): DocumentTemplate
    {
        return DocumentTemplate::findOrFail($id);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Fetch active global templates from the Mothership DB.
     * Silently returns an empty collection if the connection is unavailable,
     * so a misconfigured Mothership never breaks the tenant app.
     *
     * @param  string|null  $area  Optional area filter (null = all areas)
     */
    private function fetchGlobalTemplates(?string $area = null): Collection
    {
        try {
            $query = MothershipDocumentTemplate::active()->orderBy('titulo');

            if ($area !== null) {
                $query->forArea($area);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::warning('[LawFirm] Could not fetch global document templates from Mothership: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Parse a unique_id string like "local-5" or "global-3" into [$source, $id].
     *
     * @return array{0: string, 1: string}
     *
     * @throws \InvalidArgumentException
     */
    private function parseUniqueId(string $uniqueId): array
    {
        if (! str_contains($uniqueId, '-')) {
            // Backward-compat: plain integer treated as local
            return ['local', $uniqueId];
        }

        [$source, $id] = explode('-', $uniqueId, 2);

        if (! in_array($source, ['local', 'global'], true)) {
            throw new \InvalidArgumentException("Invalid template source: '{$source}'. Expected 'local' or 'global'.");
        }

        return [$source, $id];
    }
}
