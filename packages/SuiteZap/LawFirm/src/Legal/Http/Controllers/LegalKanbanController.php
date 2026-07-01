<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\Database\Seeders\LegalPipelineSeeder;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\LegalPipeline;
use SuiteZap\LawFirm\Legal\Services\LegalPipelineService;

/**
 * LegalKanbanController — Skinny controller for the Legal Kanban board.
 *
 * Delegates all business logic to LegalPipelineService.
 * Uses Bouncer ACL for authorization.
 */
class LegalKanbanController extends Controller
{
    protected LegalPipelineService $pipelineService;

    public function __construct(LegalPipelineService $pipelineService)
    {
        $this->pipelineService = $pipelineService;
    }

    /**
     * Display the Kanban board.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        if (bouncer()->hasPermission('lawfirm.kanban.view')) {
            // ok
        } else {
            abort(403);
        }

        $pipeline = LegalPipeline::with('stages')->first();

        if (! $pipeline) {
            // Auto-provision the pipeline on first access (idempotent seeder).
            (new LegalPipelineSeeder)->run();
            $pipeline = LegalPipeline::with('stages')->first();
        }

        if (! $pipeline) {
            return view('lawfirm::Legal.kanban.index', [
                'stages'               => collect(),
                'casosByStage'         => collect(),
                'processosTooltipJson' => '{}',
            ]);
        }

        $stages = $pipeline->stages()->orderBy('sort_order')->get();
        $firstStageId = $stages->first()?->id;

        // Respect ACL view_permission scoping
        $authorizedUserIds = bouncer()->getAuthorizedUserIds();

        $casosQuery = Caso::with(['person', 'organization', 'responsavel', 'stage']);

        // Apply user scoping if not global
        if ($authorizedUserIds !== null) {
            $casosQuery->whereIn('user_id', $authorizedUserIds);
        }

        $casos = $casosQuery->get();

        // Build a JSON map of processos keyed by caso_id for the JS tooltip.
        // This avoids ALL Blade scope / Eloquent dynamic property issues.
        $casoIds = $casos->pluck('id')->toArray();
        $rawProcessos = \Illuminate\Support\Facades\DB::table('processos')
            ->whereIn('caso_id', $casoIds)
            ->whereNotNull('caso_id')
            ->select(['id', 'titulo', 'status', 'caso_id'])
            ->get();

        $tooltipMap = [];
        foreach ($rawProcessos as $p) {
            $key = (int) $p->caso_id;
            $tooltipMap[$key][] = [
                'titulo' => \Illuminate\Support\Str::limit($p->titulo ?? 'Processo #'.$p->id, 38),
                'status' => $p->status ?? '—',
            ];
        }
        $processosTooltipJson = json_encode($tooltipMap, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

        // Group casos by stage_id — unassigned cases go to the first column
        $casosByStage = $casos->groupBy(function ($caso) use ($firstStageId) {
            return $caso->legal_pipeline_stage_id ?? $firstStageId;
        });

        return view('lawfirm::Legal.kanban.index', compact('stages', 'casosByStage', 'processosTooltipJson'));
    }

    /**
     * Update a Caso's pipeline stage (drag-and-drop handler).
     *
     * @param  int  $id  Caso ID.
     */
    public function updateStage(Request $request, int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('lawfirm.kanban.edit')) {
            return response()->json(['error' => 'Sem permissão.'], 403);
        }

        $request->validate([
            'stage_id' => 'required|integer|exists:law_legal_pipeline_stages,id',
        ]);

        $caso = Caso::findOrFail($id);

        try {
            $updatedCaso = $this->pipelineService->moveCaseToStage($caso, (int) $request->stage_id);

            return response()->json([
                'success'    => true,
                'caso_id'    => $updatedCaso->id,
                'stage_name' => $updatedCaso->status,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro ao mover caso.'], 500);
        }
    }
}
