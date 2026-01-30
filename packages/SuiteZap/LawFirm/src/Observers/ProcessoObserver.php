<?php

namespace SuiteZap\LawFirm\Observers;

use SuiteZap\LawFirm\Models\Processo;
use SuiteZap\LawFirm\Services\SaasStorageService;
use Webkul\Activity\Repositories\ActivityRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessoObserver
{
    /**
     * Handle the Processo "deleting" event.
     * Executado ANTES do registro ser removido do banco.
     * Apaga a pasta inteira do processo no S3.
     */
    public function deleting(Processo $processo): void
    {
        $folderPath = 'processos/' . $processo->id;
        $disk = config('filesystems.default');

        try {
            // Calcular tamanho total dos arquivos para decrementar quota
            $totalSize = 0;
            foreach ($processo->anexos as $anexo) {
                $totalSize += $anexo->tamanho ?? 0;
            }

            // Deletar pasta inteira do S3
            if (Storage::disk($disk)->exists($folderPath)) {
                Storage::disk($disk)->deleteDirectory($folderPath);
                Log::info("SAAS CLEANUP: Pasta S3 apagada: {$folderPath}");
            }

            // Decrementar uso de storage
            if ($totalSize > 0) {
                $storageService = app(SaasStorageService::class);
                $storageService->decrementUsage($totalSize);
                Log::info("SAAS CLEANUP: Storage decrementado em {$totalSize} bytes para Processo #{$processo->id}");
            }
        } catch (\Exception $e) {
            Log::error("SAAS CLEANUP ERROR: Falha ao apagar pasta {$folderPath}: " . $e->getMessage());
        }
    }

    /**
     * Handle the Processo "created" event.
     *
     * Cria automaticamente uma atividade na timeline do Lead
     * quando um novo Processo é criado.
     */
    public function created(Processo $processo): void
    {
        // Só cria activity se processo estiver vinculado a um Lead
        if (!$processo->lead_id) {
            return;
        }

        // Busca o nome do Lead para o título
        $lead = $processo->lead;
        $leadTitle = $lead ? $lead->title : 'Lead #' . $processo->lead_id;

        // Cria a atividade usando o repositório nativo do Krayin
        $activity = app(ActivityRepository::class)->create([
            'type' => 'lunch',  // Tipo exibido como "Processo" na interface
            'title' => 'Processo ' . $processo->numero . ' vinculado ao Lead',
            'comment' => 'Lead: ' . $leadTitle . ' | Status: ' . $processo->status . ($processo->vara ? ' | Vara: ' . $processo->vara : ''),
            'schedule_from' => now(),
            'schedule_to' => now(),
            'is_done' => 0,
            'user_id' => auth()->id() ?? 1,
        ]);

        // Vincula a atividade ao Lead via tabela pivot lead_activities
        $activity->leads()->attach($processo->lead_id);
    }

    /**
     * Handle the Processo "updated" event.
     *
     * Opcionalmente, pode-se criar uma atividade para mudanças de status.
     */
    public function updated(Processo $processo): void
    {
        // Se o status mudou e existe um lead vinculado, registra a alteração
        if ($processo->isDirty('status') && $processo->lead_id) {
            $lead = $processo->lead;
            $leadTitle = $lead ? $lead->title : 'Lead #' . $processo->lead_id;

            $activity = app(ActivityRepository::class)->create([
                'type' => 'lunch',
                'title' => 'Processo ' . $processo->numero . ' - Status Alterado',
                'comment' => 'Lead: ' . $leadTitle . ' | Novo status: ' . $processo->status,
                'schedule_from' => now(),
                'schedule_to' => now(),
                'is_done' => 0,
                'user_id' => auth()->id() ?? 1,
            ]);

            $activity->leads()->attach($processo->lead_id);
        }
    }
}

