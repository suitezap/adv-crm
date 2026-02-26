<?php

namespace SuiteZap\LawFirm\Legal\Observers;

use Carbon\Carbon;
use SuiteZap\LawFirm\Legal\Models\Processo;
use Webkul\Activity\Repositories\ActivityRepository;

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
     * Handle the Processo "deleted" event.
     *
     * @param  \SuiteZap\LawFirm\Legal\Models\Processo  $processo
     * @return void
     */
    public function deleted(Processo $processo)
    {
        $this->forceCleanupCalendarEvent($processo);
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
        // Se usar guard 'user' falhar, pega o admin de forma falback. Se não houver auth, não cria log/calendar.
        $userId = auth()->guard('user')->id() ?? $processo->user_id;

        if (!$userId) {
            return;
        }

        $tag = "[REF:PROC_ID:{$processo->id}]";

        // 1. Find existing activity by TAG
        $activities = $this->activityRepository->findWhere([
            'type' => 'meeting',
            'is_done' => 0,
            'user_id' => $userId
        ]);

        // Filter collection to find the specific tag in comment
        $existingActivity = $activities->first(function ($activity) use ($tag) {
            return str_contains($activity->comment, $tag);
        });

        // 2. Determine Action: Cleanup OR Upsert
        $isActive = strtolower(trim($processo->status)) === 'ativo';
        $hasDate = !empty($processo->data_audiencia);

        if (!$isActive || !$hasDate) {
            // Case A: Cleanup (Not active OR no date) -> Delete if exists
            if ($existingActivity) {
                $this->activityRepository->delete($existingActivity->id);
            }
            return;
        }

        // Case B: Upsert (Active AND has date)
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
            'process_id' => $processo->id, // If custom column exists
            'participants' => [
                'users' => [$userId]
            ]
        ];

        if ($existingActivity) {
            // Update
            $this->activityRepository->update($payload, $existingActivity->id);
        } else {
            // Create
            $this->activityRepository->create($payload);
        }
    }

    /**
     * Force clean up calendar events when a process is soft-deleted or force-deleted.
     *
     * @param Processo $processo
     * @return void
     */
    private function forceCleanupCalendarEvent(Processo $processo)
    {
        $tag = "[REF:PROC_ID:{$processo->id}]";

        $activities = $this->activityRepository->findWhere([
            'type' => 'meeting',
            'is_done' => 0,
        ]);

        $existingActivity = $activities->first(function ($activity) use ($tag) {
            return str_contains($activity->comment, $tag);
        });

        if ($existingActivity) {
            $this->activityRepository->delete($existingActivity->id);
        }
    }
}
