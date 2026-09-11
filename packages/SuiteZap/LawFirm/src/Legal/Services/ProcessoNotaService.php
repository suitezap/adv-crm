<?php

namespace SuiteZap\LawFirm\Legal\Services;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Models\ProcessoNota;

class ProcessoNotaService
{
    /**
     * Synchronize notes for a given process based on request data.
     *
     * The array should have the shape:
     * [
     *     0 => ['id' => '1', 'nota' => '...'],
     *     1 => ['id' => '', 'nota' => '...']
     * ]
     *
     * Missing IDs that previously existed will be deleted.
     * Empty IDs will be created.
     *
     * @return void
     */
    public function syncNotas(Processo $processo, array $notasData)
    {
        Log::info("ProcessoNotaService::syncNotas starting for Processo ID: {$processo->id}");

        $existingNotaIds = $processo->notas()->pluck('id')->toArray();
        $submittedNotaIds = collect($notasData)->pluck('id')->filter()->toArray();

        // 1. Delete notes that are no longer in the request
        $notasToDelete = array_diff($existingNotaIds, $submittedNotaIds);
        if (! empty($notasToDelete)) {
            ProcessoNota::whereIn('id', $notasToDelete)->delete();
            Log::info('ProcessoNotaService: Deleted notes IDs: '.implode(',', $notasToDelete));
        }

        // 2. Create or Update notes
        $userId = auth()->guard('user')->id();

        foreach ($notasData as $data) {
            // Skip completely empty notes to prevent garbage rows
            if (empty(trim($data['nota']))) {
                continue;
            }

            if (! empty($data['id'])) {
                // Update existing (though typically notes are append-only, editing is allowed if ID exists)
                $nota = ProcessoNota::find($data['id']);
                if ($nota && $nota->processo_id == $processo->id) {
                    $nota->update(['nota' => $data['nota']]);
                }
            } else {
                // Create new
                ProcessoNota::create([
                    'processo_id' => $processo->id,
                    'user_id'     => $userId,
                    'nota'        => $data['nota'],
                ]);
            }
        }

        Log::info('ProcessoNotaService::syncNotas completed.');
    }
}
