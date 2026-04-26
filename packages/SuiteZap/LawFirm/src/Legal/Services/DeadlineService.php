<?php

namespace SuiteZap\LawFirm\Legal\Services;

use SuiteZap\LawFirm\Legal\Models\Prazo;
use SuiteZap\LawFirm\Legal\Models\Processo;
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

        // Normalize status to canonical lowercase without accents
        // Handles: 'Concluído', 'concluído', 'Pendente', 'pendente', 'concluido'
        $rawStatus = $data['status'] ?? $prazo->status;
        $newStatus = strtolower(str_replace(['í', 'Í'], 'i', $rawStatus));

        // Logic for handling status change and conclusion date
        $concluidoEm = null;

        if ($newStatus === 'concluido') {
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
            'titulo'          => $data['titulo'] ?? $prazo->titulo,
            'data_vencimento' => isset($data['data_vencimento']) ? Carbon::parse($data['data_vencimento'])->format('Y-m-d H:i:s') : $prazo->data_vencimento,
            'tipo'            => $data['tipo'] ?? $prazo->tipo,
            'status'          => $newStatus,
            'descricao'       => $data['descricao'] ?? $prazo->descricao,
            'concluido_em'    => $concluidoEm,
        ];

        $prazo->update($updateData);

        Log::info("DeadlineService: Updated deadline ID {$prazo->id} status={$newStatus}");

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
     * Sync deadlines for a process (Create, Update, Delete).
     *
     * @param mixed $processo Processo object or ID
     * @param array $deadlinesData
     * @return void
     */
    public function syncDeadlines($processo, array $deadlinesData)
    {
        if (!is_array($deadlinesData))
            return;

        $processoId = $processo instanceof Processo ? $processo->id : $processo;

        foreach ($deadlinesData as $data) {
            // If marked for deletion
            if (isset($data['should_delete']) && $data['should_delete'] == 1) {
                if (isset($data['id'])) {
                    $this->deleteDeadline($data['id']);
                }
                continue;
            }

            // Create or Update
            Prazo::updateOrCreate(
                ['id' => $data['id'] ?? null],
                array_merge($data, ['processo_id' => $processoId])
            );
        }
    }

    /**
     * Delete a deadline.
     *
     * @param int $id
     * @return bool
     */
    public function deleteDeadline(int $id): bool
    {
        $prazo = Prazo::findOrFail($id);

        // Remove Activity if exists
        if ($prazo->activity_id) {
            // Note: Activity cleanup logic handles this observer-side usually,
            // but we can ensure it here if needed.
            // For now, let's rely on standard delete.
        }

        return $prazo->delete();
    }

    /**
     * Sync (create or update) the Audiência prazo for a process.
     * Called when data_audiencia changes on a Processo.
     *
     * @param  Processo  $processo
     * @param  string|null  $dataAudiencia  The raw datetime string from the form
     * @return void
     */
    public function syncAudienciaPrazo(Processo $processo, ?string $dataAudiencia): void
    {
        if (empty($dataAudiencia)) {
            return;
        }

        try {
            $vencimento = Carbon::parse($dataAudiencia)->startOfDay()->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning("DeadlineService::syncAudienciaPrazo — could not parse '{$dataAudiencia}': " . $e->getMessage());
            return;
        }

        // Find existing audiência prazo for this process
        $existing = Prazo::where('processo_id', $processo->id)
            ->where('titulo', 'LIKE', '%Audiência%')
            ->where('status', '!=', 'concluido')
            ->first();

        if ($existing) {
            $existing->update(['data_vencimento' => $vencimento]);
            Log::info("DeadlineService: Updated audiência prazo ID {$existing->id} for processo {$processo->id}");
        } else {
            $prazo = $processo->prazos()->create([
                'titulo'          => '📅 Audiência',
                'data_vencimento' => $vencimento,
                'tipo'            => 'fatal',
                'status'          => 'pendente',
                'descricao'       => 'Prazo criado automaticamente a partir da Data da Audiência.',
            ]);
            Log::info("DeadlineService: Created audiência prazo ID {$prazo->id} for processo {$processo->id}");
        }
    }
}
