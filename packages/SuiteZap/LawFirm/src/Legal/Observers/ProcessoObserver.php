<?php

namespace SuiteZap\LawFirm\Legal\Observers;

use Carbon\Carbon;
use SuiteZap\LawFirm\Legal\Models\Processo;
use Webkul\Activity\Repositories\ActivityRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessoObserver
{
    /**
     * @var ActivityRepository
     */
    protected $activityRepository;

    public function __construct(ActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    /**
     * Handle the Processo "saved" event (Create or Update).
     *
     * @param  \SuiteZap\LawFirm\Legal\Models\Processo  $processo
     * @return void
     */
    public function saved(Processo $processo)
    {
        $this->ensureCalendarEvent($processo);
    }

    /**
     * Handle the Processo "deleting" event.
     * Fires BEFORE the DB row is removed, enabling us to cascade deletions
     * through Eloquent observers (which SQL-CASCADE would bypass entirely).
     *
     * @param  \SuiteZap\LawFirm\Legal\Models\Processo  $processo
     * @return void
     */
    public function deleting(Processo $processo)
    {
        // 1. Prazos — triggers PrazoObserver::deleted which removes linked Calendar Activities
        $processo->prazos->each(function ($prazo) {
            $prazo->delete();
        });

        // 2. Anexos — delete from S3, then remove DB row
        $processo->anexos->each(function ($anexo) {
            if (!empty($anexo->path)) {
                try {
                    Storage::disk('s3')->delete($anexo->path);
                } catch (\Throwable $e) {
                    Log::warning("ProcessoObserver: Failed S3 delete for Anexo path [{$anexo->path}]: " . $e->getMessage());
                }
            }
            $anexo->delete();
        });

        // 3. ProcessDocuments (GED template docs) — delete from S3, then remove DB row
        $processo->documents->each(function ($doc) {
            if (!empty($doc->file_path)) {
                try {
                    Storage::disk('s3')->delete($doc->file_path);
                } catch (\Throwable $e) {
                    Log::warning("ProcessoObserver: Failed S3 delete for ProcessDocument path [{$doc->file_path}]: " . $e->getMessage());
                }
            }
            $doc->delete();
        });

        // 4. Notas — quick bulk delete (no observer needed)
        $processo->notas()->delete();

        // 5. Financeiros — quick bulk delete  
        $processo->financeiros()->delete();

        // 6. Clean up the Audiência Calendar Event
        $this->forceCleanupCalendarEvent($processo);
    }

    /**
     * Handle the Processo "deleted" event.
     *
     * @param  \SuiteZap\LawFirm\Legal\Models\Processo  $processo
     * @return void
     */
    public function deleted(Processo $processo)
    {
        // Cascade is handled in deleting(). This hook is kept for future extensibility.
    }

    /**
     * Ensure calendar event logic: Create, Update OR Delete based on state.
     * Uses [REF:PROC_ID:{id}] tag to strictly identify the event.
     *
     * @param Processo $processo
     * @return void
     */
    private function ensureCalendarEvent(Processo $processo)
    {
        $userId = auth()->guard('user')->id() ?? $processo->user_id;

        if (!$userId) {
            return;
        }

        $tag = "[REF:PROC_ID:{$processo->id}]";

        $activities = $this->activityRepository->findWhere([
            'type' => 'meeting',
            'is_done' => 0,
            'user_id' => $userId
        ]);

        $existingActivity = $activities->first(function ($activity) use ($tag) {
            return str_contains($activity->comment ?? '', $tag);
        });

        $isActive = strtolower(trim($processo->status)) === 'ativo';
        $hasDate = !empty($processo->data_audiencia);

        if (!$isActive || !$hasDate) {
            if ($existingActivity) {
                $this->activityRepository->delete($existingActivity->id);
            }
            return;
        }

        $scheduledFrom = Carbon::parse($processo->data_audiencia);
        $scheduledTo = $scheduledFrom->copy()->addHour();
        $title = 'Audiência: ' . $processo->titulo;
        $comment = "Audiência gerada automaticamente pelo Processo Nº {$processo->numero_cnj}. {$tag}";

        $payload = [
            'type' => 'meeting',
            'title' => $title,
            'comment' => "<p>{$comment}</p>",
            'schedule_from' => $scheduledFrom->format('Y-m-d H:i:s'),
            'schedule_to' => $scheduledTo->format('Y-m-d H:i:s'),
            'is_done' => 0,
            'user_id' => $userId,
            'process_id' => $processo->id,
            'participants' => [
                'users' => [$userId]
            ]
        ];

        if ($existingActivity) {
            $this->activityRepository->update($payload, $existingActivity->id);
        } else {
            $this->activityRepository->create($payload);
        }
    }

    /**
     * Force clean up ALL calendar events tagged for a given processo —
     * including is_done=1 entries that the previous cleanup logic missed.
     *
     * @param Processo $processo
     * @return void
     */
    private function forceCleanupCalendarEvent(Processo $processo)
    {
        $tag = "[REF:PROC_ID:{$processo->id}]";

        $all = $this->activityRepository->findWhere(['type' => 'meeting']);

        $all->filter(function ($activity) use ($tag) {
            return str_contains($activity->comment ?? '', $tag);
        })->each(function ($activity) {
            $this->activityRepository->delete($activity->id);
        });
    }
}
