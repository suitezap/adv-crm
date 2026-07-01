<?php

namespace SuiteZap\LawFirm\Legal\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\Processo;

/**
 * SyncProcessoStatusJob — Async cascade update for large Caso volumes.
 *
 * Dispatched by LegalPipelineService when a Caso has more than 10 child
 * Processos. Performs a single batch UPDATE without N+1 queries.
 *
 * Follows Graceful Degradation: catches exceptions, logs errors,
 * never throws uncaught to prevent queue worker crashes.
 */
class SyncProcessoStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $casoId;

    public string $statusName;

    /**
     * Maximum attempts before marking as failed.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(int $casoId, string $statusName)
    {
        $this->casoId = $casoId;
        $this->statusName = $statusName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $count = Processo::where('caso_id', $this->casoId)
                ->update(['status' => $this->statusName]);

            Log::info("SyncProcessoStatusJob: Updated {$count} processos for Caso #{$this->casoId} to '{$this->statusName}'.");
        } catch (\Throwable $e) {
            Log::error("SyncProcessoStatusJob: Failed to sync processos for Caso #{$this->casoId}. Error: {$e->getMessage()}");
        }
    }
}
