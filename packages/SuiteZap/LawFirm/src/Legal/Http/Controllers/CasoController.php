<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use SuiteZap\LawFirm\Legal\DataGrids\CasoDataGrid;
use SuiteZap\LawFirm\Legal\Http\Requests\StoreCasoRequest;
use SuiteZap\LawFirm\Legal\Http\Requests\UpdateCasoRequest;
use SuiteZap\LawFirm\Legal\Repositories\CasoRepository;
use SuiteZap\LawFirm\Legal\Services\CasoService;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Contact\Repositories\OrganizationRepository;
use Webkul\Contact\Repositories\PersonRepository;

class CasoController extends Controller
{
    protected CasoRepository $casoRepository;

    protected CasoService $casoService;

    protected PersonRepository $personRepository;

    protected OrganizationRepository $organizationRepository;

    public function __construct(
        CasoRepository $casoRepository,
        CasoService $casoService,
        PersonRepository $personRepository,
        OrganizationRepository $organizationRepository
    ) {
        $this->casoRepository = $casoRepository;
        $this->casoService = $casoService;
        $this->personRepository = $personRepository;
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(CasoDataGrid::class)->process();
        }

        return view('lawfirm::Legal.casos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! bouncer()->hasPermission('lawfirm.casos.create'), 401, 'This action is unauthorized');

        return view('lawfirm::Legal.casos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCasoRequest $request)
    {
        $data = $request->validated();

        $caso = $this->casoService->createCaso($data);

        session()->flash('success', 'Caso criado com sucesso.');

        return redirect()->route('admin.lawfirm.casos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $caso = $this->casoRepository->findOrFail($id);
        $caso->load(['person', 'organization', 'responsavel', 'processos']);

        $kpis = $this->casoService->getKPIs($caso);

        // Fetch AI Context (Triagem) from the lead linked to one of the linked processos
        $triagem = null;
        $processoWithLead = $caso->processos->whereNotNull('lead_id')->first();
        if ($processoWithLead) {
            $triagem = \SuiteZap\LawFirm\AI\Models\LeadTriagem::where('lead_id', $processoWithLead->lead_id)->first();
        }

        return view('lawfirm::Legal.casos.show', compact('caso', 'kpis', 'triagem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.casos.edit'), 401, 'This action is unauthorized');

        $caso = $this->casoRepository->findOrFail($id);
        $caso->load(['person', 'organization', 'responsavel', 'processos']);

        $kpis = $this->casoService->getKPIs($caso);

        return view('lawfirm::Legal.casos.edit', compact('caso', 'kpis'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCasoRequest $request, int $id)
    {
        $data = $request->validated();

        $caso = $this->casoService->updateCaso($data, $id);

        session()->flash('success', 'Caso atualizado com sucesso.');

        return redirect()->route('admin.lawfirm.casos.edit', $caso->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.casos.delete'), 401, 'This action is unauthorized');

        try {
            $this->casoRepository->delete($id);

            return response()->json([
                'message' => 'Caso excluído com sucesso.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir o caso.',
            ], 500);
        }
    }

    /**
     * Mass destroy the specified resources from storage.
     */
    public function massDestroy()
    {
        try {
            $indices = request()->input('indices', []);

            if (empty($indices)) {
                return response()->json([
                    'message' => 'Nenhum caso selecionado.',
                ], 400);
            }

            $casos = $this->casoRepository->findWhereIn('id', $indices);
            $deletedCount = 0;

            foreach ($casos as $caso) {
                $this->casoRepository->delete($caso->id);
                $deletedCount++;
            }

            return response()->json([
                'message' => $deletedCount.' caso(s) excluído(s) com sucesso.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir os casos.',
            ], 500);
        }
    }

    /**
     * Search caso results (for v-lookup / AJAX select).
     */
    public function searchCaso()
    {
        $term = request('query');

        $results = $this->casoRepository->scopeQuery(function ($query) use ($term) {
            return $query->where('titulo', 'like', '%'.$term.'%');
        })->paginate(10);

        return response()->json($results);
    }

    /**
     * Search processos for linking to a caso (AJAX).
     * Excludes processos already linked to this caso.
     */
    public function searchProcesso()
    {
        $term = request('query');
        $casoId = request('caso_id');

        $results = \SuiteZap\LawFirm\Legal\Models\Processo::query()
            ->where(function ($q) use ($term) {
                $q->where('titulo', 'like', '%'.$term.'%')
                    ->orWhere('numero_cnj', 'like', '%'.$term.'%')
                    ->orWhere('id', $term);
            })
            ->where(function ($q) use ($casoId) {
                // Exclude processos already linked to THIS caso
                // Must handle NULL explicitly: NULL != X is UNKNOWN in SQL
                $q->whereNull('caso_id')
                    ->orWhere('caso_id', '!=', $casoId);
            })
            ->select('id', 'titulo', 'numero_cnj', 'status', 'caso_id')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    /**
     * Link an existing processo to a caso (set caso_id).
     */
    public function linkProcesso(int $id)
    {
        $processoId = request('processo_id');

        $caso = $this->casoRepository->findOrFail($id);
        $processo = \SuiteZap\LawFirm\Legal\Models\Processo::findOrFail($processoId);

        $processo->update(['caso_id' => $caso->id]);

        return response()->json([
            'message' => "Processo #{$processo->id} vinculado ao Caso #{$caso->id} com sucesso.",
        ]);
    }

    /**
     * Unlink a processo from a caso (set caso_id to null).
     */
    public function unlinkProcesso(int $id, int $processoId)
    {
        $processo = \SuiteZap\LawFirm\Legal\Models\Processo::where('id', $processoId)
            ->where('caso_id', $id)
            ->firstOrFail();

        $processo->update(['caso_id' => null]);

        return response()->json([
            'message' => "Processo #{$processo->id} desvinculado com sucesso.",
        ]);
    }
}
