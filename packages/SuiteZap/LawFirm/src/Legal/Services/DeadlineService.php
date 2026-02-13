<?php

namespace SuiteZap\LawFirm\Legal\Services;

use SuiteZap\LawFirm\Models\Prazo;
use SuiteZap\LawFirm\Models\Processo;
use SuiteZap\LawFirm\Events\PrazoCreated;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class DeadlineService
{
    /**
     * Create a new deadline for a given process.
     *
     * @param array $data
     * @return Prazo
     * @throws ModelNotFoundException
     */
    public function createDeadline(array $data): Prazo
    {
        // 1. Validate Process Existence
        $processo = Processo::findOrFail($data['processo_id']);

        // 2. Format Date
        $vencimento = Carbon::parse($data['data_vencimento'])->format('Y-m-d H:i:s');

        // 3. Create Prazo
        $prazo = $processo->prazos()->create([
            'titulo' => $data['titulo'],
            'data_vencimento' => $vencimento,
            'tipo' => $data['tipo'] ?? 'comum',
            'descricao' => $data['descricao'] ?? null,
            'status' => 'pendente',
            'activity_id' => $data['activity_id'] ?? null,
        ]);

        // 4. Dispatch Event
        event(new PrazoCreated($prazo));

        Log::info("DeadlineService: Created deadline ID {$prazo->id} for process {$processo->id}");

        return $prazo;
    }

    /**
     * Update an existing deadline.
     *
     * @param int $id
     * @param array $data
     * @return Prazo
     */
    public function updateDeadline(int $id, array $data): Prazo
    {
        $prazo = Prazo::findOrFail($id);

        // Logic for handling status change and conclusion date
        $newStatus = $data['status'] ?? $prazo->status;
        $concluidoEm = null;

        if ($newStatus === 'concluido' || $newStatus === 'concluído') {
            // If explicitly setting to concluded, update date if not already set
            $concluidoEm = $prazo->concluido_em ?? Carbon::now();
        } elseif ($newStatus === 'pendente') {
            // If reverting to pending, clear date
            $concluidoEm = null;
        } else {
            // Maintain existing if status is not changing in a way that affects date logic
            $concluidoEm = $prazo->concluido_em;
        }

        $updateData = [
            'titulo' => $data['titulo'] ?? $prazo->titulo,
            'data_vencimento' => isset($data['data_vencimento']) ? Carbon::parse($data['data_vencimento'])->format('Y-m-d H:i:s') : $prazo->data_vencimento,
            'tipo' => $data['tipo'] ?? $prazo->tipo,
            'status' => $newStatus,
            'descricao' => $data['descricao'] ?? $prazo->descricao,
            'concluido_em' => $concluidoEm,
        ];

        $prazo->update($updateData);

        Log::info("DeadlineService: Updated deadline ID {$prazo->id}");

        return $prazo;
    }

    /**
     * Toggle the status of a deadline between pending and concluded.
     *
     * @param int $id
     * @return Prazo
     */
    public function toggleStatus(int $id): Prazo
    {
        $prazo = Prazo::findOrFail($id);

        $currentStatus = strtolower($prazo->status);
        $isConcluded = ($currentStatus === 'concluido' || $currentStatus === 'concluído');

        if ($isConcluded) {
            $prazo->update([
                'status' => 'pendente',
                'concluido_em' => null
            ]);
        } else {
            $prazo->update([
                'status' => 'concluido',
                'concluido_em' => Carbon::now()
            ]);
        }

        Log::info("DeadlineService: Toggled deadline ID {$prazo->id} status to " . ($isConcluded ? 'pendente' : 'concluido'));

        return $prazo;
    }

    /**
     * Mark a deadline as completed.
     *
     * @param int $id
     * @return Prazo
     */
    public function completeDeadline(int $id): Prazo
    {
        $prazo = Prazo::findOrFail($id);

        $prazo->update([
            'status' => 'concluido',
            'concluido_em' => Carbon::now()
        ]);

        Log::info("DeadlineService: Completed deadline ID {$prazo->id}");

        return $prazo;
    }

    /**
     * Delete a deadline.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteDeadline(int $id): ?bool
    {
        $prazo = Prazo::findOrFail($id);
        Log::info("DeadlineService: Deleting deadline ID {$prazo->id}");
        return $prazo->delete();
    }
}
