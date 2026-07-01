<?php

namespace SuiteZap\LawFirm\Legal\Observers;

use Carbon\Carbon;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Services\DeadlineService;
use Webkul\Activity\Models\Activity;

class ActivityObserver
{
    /**
     * Handle the Activity "updated" event.
     * Sincronização Reversa: Se uma Atividade do Krayin (gerada por um Processo)
     * tiver sua data alterada, reflete essa mudança no Processo e no Prazo.
     */
    public function updated(Activity $activity): void
    {
        // Verifica se é uma atividade de audiência vinculada a um processo (tag [REF:PROC_ID:XX])
        if (str_contains($activity->comment ?? '', '[REF:PROC_ID:')) {
            preg_match('/\[REF:PROC_ID:(\d+)\]/', $activity->comment, $matches);

            if (! empty($matches[1])) {
                $processoId = $matches[1];
                $processo = Processo::find($processoId);

                // Se o processo existe e a data mudou na atividade (após um drag & drop ou edição)
                // Usamos toDateTimeString() para podermos comparar strings ou comparar objetos Carbon
                if ($processo && $processo->data_audiencia) {
                    $originalDate = Carbon::parse($processo->data_audiencia)->format('Y-m-d H:i');
                    $newDate = Carbon::parse($activity->schedule_from)->format('Y-m-d H:i');

                    if ($originalDate !== $newDate) {
                        // 1. Atualizar a data no processo sem disparar os observers de Processo (evita loop infinito)
                        $processo->data_audiencia = $activity->schedule_from;
                        $processo->saveQuietly();

                        // 2. Sincronizar o Prazo Automático para também constar na data/hora
                        app(DeadlineService::class)->syncAudienciaPrazo($processo, $activity->schedule_from);
                    }
                }
            }
        }
    }
}
