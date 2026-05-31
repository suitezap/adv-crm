<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use SuiteZap\LawFirm\Legal\DataGrids\ProcessoDataGrid;
use SuiteZap\LawFirm\Legal\Repositories\ProcessoRepository;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Contact\Repositories\OrganizationRepository;
use Webkul\Lead\Repositories\LeadRepository;
use SuiteZap\LawFirm\GED\Services\DocumentService;
use SuiteZap\LawFirm\Legal\Services\DeadlineService;
use SuiteZap\LawFirm\Legal\Services\ProcessoNotaService;
use SuiteZap\LawFirm\Financial\Services\FinancialService;

class ProcessoController extends Controller
{
    /**
     * ProcessoRepository object
     *
     * @var \SuiteZap\LawFirm\Legal\Repositories\ProcessoRepository
     */
    protected $processoRepository;

    /**
     * PersonRepository object
     *
     * @var \Webkul\Contact\Repositories\PersonRepository
     */
    protected $personRepository;

    /**
     * OrganizationRepository object
     *
     * @var \Webkul\Contact\Repositories\OrganizationRepository
     */
    protected $organizationRepository;

    /**
     * LeadRepository object
     *
     * @var \Webkul\Lead\Repositories\LeadRepository
     */
    protected $leadRepository;

    /**
     * ActivityRepository object
     *
     * @var \Webkul\Activity\Repositories\ActivityRepository
     */
    protected $activityRepository;

    /**
     * DocumentService object
     *
     * @var \SuiteZap\LawFirm\GED\Services\DocumentService
     */
    protected $documentService;

    /**
     * DeadlineService object
     *
     * @var \SuiteZap\LawFirm\Legal\Services\DeadlineService
     */
    protected $deadlineService;

    /**
     * FinancialService object
     *
     * @var \SuiteZap\LawFirm\Financial\Services\FinancialService
     */
    protected $financialService;

    /**
     * ProcessoNotaService object
     *
     * @var \SuiteZap\LawFirm\Legal\Services\ProcessoNotaService
     */
    protected $processoNotaService;

    /**
     * Create a new controller instance.
     *
     * @param  \SuiteZap\LawFirm\Repositories\ProcessoRepository  $processoRepository
     * @param  \Webkul\Contact\Repositories\PersonRepository  $personRepository
     * @param  \Webkul\Lead\Repositories\LeadRepository  $leadRepository
     * @param  \Webkul\Activity\Repositories\ActivityRepository  $activityRepository
     * @return void
     */
    public function __construct(
        ProcessoRepository $processoRepository,
        PersonRepository $personRepository,
        OrganizationRepository $organizationRepository,
        LeadRepository $leadRepository,
        ActivityRepository $activityRepository,
        DocumentService $documentService,
        DeadlineService $deadlineService,
        FinancialService $financialService,
        ProcessoNotaService $processoNotaService
    ) {
        $this->processoRepository = $processoRepository;
        $this->personRepository = $personRepository;
        $this->organizationRepository = $organizationRepository;
        $this->leadRepository = $leadRepository;
        $this->activityRepository = $activityRepository;
        $this->documentService = $documentService;
        $this->deadlineService = $deadlineService;
        $this->financialService = $financialService;
        $this->processoNotaService = $processoNotaService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ProcessoDataGrid::class)->process();
        }

        return view('lawfirm::admin.processos.index');
    }

    /**
     * Display a listing of the resource for a specific lead.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function leadProcessos($id)
    {
        return app(\SuiteZap\LawFirm\Legal\DataGrids\LeadProcessosDataGrid::class)->process();
    }

    /**
     * Display a listing of the resource for a specific person.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function personProcessos($id)
    {
        return app(\SuiteZap\LawFirm\Legal\DataGrids\PersonProcessosDataGrid::class)->process();
    }

    /**
     * Display a listing of the resource for a specific organization.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function organizationProcessos($id)
    {
        return app(\SuiteZap\LawFirm\Legal\DataGrids\OrganizationProcessosDataGrid::class)->process();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        abort_if(!bouncer()->hasPermission('lawfirm.processos.create'), 401, 'This action is unauthorized');

        $leadId = request('lead_id');
        $preSelectedLead = null;

        if ($leadId) {
            $preSelectedLead = $this->leadRepository->find($leadId);
        }

        $persons = $this->personRepository->all();
        $leads = $this->leadRepository->all();

        return view('lawfirm::admin.processos.create', compact('persons', 'leads', 'preSelectedLead'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \SuiteZap\LawFirm\Legal\Http\Requests\StoreProcessoRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(\SuiteZap\LawFirm\Legal\Http\Requests\StoreProcessoRequest $request)
    {
        $data = $request->validated();

        $data['person_id'] = !empty($data['person_id']) ? $data['person_id'] : null;
        $data['organization_id'] = !empty($data['organization_id']) ? $data['organization_id'] : null;
        $data['lead_id'] = !empty($data['lead_id']) ? $data['lead_id'] : null;
        $data['caso_id'] = !empty($data['caso_id']) ? $data['caso_id'] : null;
        $data['user_id'] = !empty($data['user_id']) ? $data['user_id'] : null;


        Event::dispatch('lawfirm.processo.create.before');

        $processo = $this->processoRepository->create($data);

        // CREATE PRAZOS (Delegated to DeadlineService)
        if (isset($data['prazos']) && is_array($data['prazos'])) {
            $this->deadlineService->syncDeadlines($processo, $data['prazos']);
        }

        // Sincronizar prazo de Audiência automaticamente
        if (!empty($data['data_audiencia'])) {
            $this->deadlineService->syncAudienciaPrazo($processo, $data['data_audiencia']);
        }

        // SYNC NOTAS (Delegated to ProcessoNotaService)
        if (isset($data['notas']) && is_array($data['notas'])) {
            $this->processoNotaService->syncNotas($processo, $data['notas']);
        }

        // CREATE FINANCEIROS (Delegated to FinancialService)
        // CREATE FINANCEIROS (Handled by isolated component via AJAX)
        // if (isset($data['financeiros']) && is_array($data['financeiros'])) {
        //     $this->financialService->syncFinancials($processo, $data['financeiros']);
        // }

        // PROCESS UPLOADS (Delegated to DocumentService)
        if (request()->hasFile('anexos') || request()->hasFile('anexo')) {
            $this->documentService->processUploads($processo, request());
        }

        Event::dispatch('lawfirm.processo.create.after', $processo);

        session()->flash('success', trans('lawfirm::app.processos.create-success'));

        return redirect()->route('admin.processos.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $processo = $this->processoRepository->findOrFail($id);
        $processo->load([
            'person',
            'lead',
            'financeiros' => function ($query) {
                $query->orderByRaw("CASE WHEN status = 'pendente' THEN 1 ELSE 2 END ASC")
                    ->orderBy('data_vencimento', 'asc');
            }
        ]);

        // Get LeadTriagem if lead exists
        $triagem = null;
        if ($processo->lead_id) {
            $triagem = \SuiteZap\LawFirm\AI\Models\LeadTriagem::where('lead_id', $processo->lead_id)->first();
        }

        return view('lawfirm::admin.processos.show', compact('processo', 'triagem'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        abort_if(!bouncer()->hasPermission('lawfirm.processos.edit'), 401, 'This action is unauthorized');

        $processo = \SuiteZap\LawFirm\Legal\Models\Processo::with([
            'person',
            'lead',
            'responsavel',
            'caso',
            'prazos',
            'anexos',
            'documents',
            'financeiros' => function ($query) {
                $query->orderByRaw("CASE WHEN status = 'pendente' THEN 1 ELSE 2 END")
                    ->orderBy('data_vencimento', 'asc');
            }
        ])->findOrFail($id);

        $persons = $this->personRepository->all();
        $leads = $this->leadRepository->all();

        return view('lawfirm::admin.processos.edit', compact('processo', 'persons', 'leads'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(\SuiteZap\LawFirm\Legal\Http\Requests\UpdateProcessoRequest $request, $id)
    {
        $data = $request->validated();

        $data['person_id'] = !empty($data['person_id']) ? $data['person_id'] : null;
        $data['organization_id'] = !empty($data['organization_id']) ? $data['organization_id'] : null;
        if (array_key_exists('lead_id', $data)) {
            $data['lead_id'] = !empty($data['lead_id']) ? $data['lead_id'] : null;
        }
        $data['caso_id'] = !empty($data['caso_id']) ? $data['caso_id'] : null;
        $data['user_id'] = !empty($data['user_id']) ? $data['user_id'] : null;

        Event::dispatch('lawfirm.processo.update.before', $id);

        $processo = $this->processoRepository->update($data, $id);

        // 2. Processar Uploads
        if ($request->hasFile('anexos') || $request->hasFile('anexo')) {
            $this->documentService->processUploads($processo, request());
        }

        // 3. Sincronizar Prazos
        if ($request->has('prazos')) {
            $this->deadlineService->syncDeadlines($processo, $request->input('prazos'));
        }

        // 3b. Sincronizar prazo de Audiência automaticamente
        if (!empty($data['data_audiencia'])) {
            $this->deadlineService->syncAudienciaPrazo($processo, $data['data_audiencia']);
        }

        // 4. Sincronizar Notas
        if ($request->has('notas')) {
            $this->processoNotaService->syncNotas($processo, $request->input('notas'));
        }

        Event::dispatch('lawfirm.processo.update.after', $processo);

        session()->flash('success', 'Processo atualizado com sucesso.');

        return redirect()->route('admin.processos.edit', $processo->id);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        abort_if(!bouncer()->hasPermission('lawfirm.processos.delete'), 401, 'This action is unauthorized');

        try {
            Event::dispatch('lawfirm.processo.delete.before', $id);

            // Observers tratam da exclusão de events

            $this->processoRepository->delete($id);

            Event::dispatch('lawfirm.processo.delete.after', $id);

            return response()->json([
                'message' => trans('lawfirm::app.processos.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('lawfirm::app.processos.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass destroy the specified resources from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function massDestroy()
    {
        try {
            $indices = request()->input('indices', []);

            if (empty($indices)) {
                return response()->json([
                    'message' => trans('lawfirm::app.processos.mass-delete.no-selection'),
                ], 400);
            }

            $processos = $this->processoRepository->findWhereIn('id', $indices);
            $deletedCount = 0;

            foreach ($processos as $processo) {
                Event::dispatch('lawfirm.processo.delete.before', $processo->id);

                // Observers do limparem a activity correspondente agora

                $this->processoRepository->delete($processo->id);

                Event::dispatch('lawfirm.processo.delete.after', $processo->id);

                $deletedCount++;
            }

            return response()->json([
                'message' => trans('lawfirm::app.processos.mass-delete.success', ['count' => $deletedCount]),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('lawfirm::app.processos.mass-delete.failed'),
            ], 500);
        }
    }

    /**
     * Search person results.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchPerson()
    {
        $term = request('query');

        $results = $this->personRepository->scopeQuery(function ($query) use ($term) {
            return $query->where('name', 'like', '%' . $term . '%');
        })->paginate(10);

        return response()->json($results);
    }

    /**
     * Search organization results.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchOrganization()
    {
        $term = request('query');

        $results = $this->organizationRepository->scopeQuery(function ($query) use ($term) {
            return $query->where('name', 'like', '%' . $term . '%');
        })->paginate(10);

        return response()->json($results);
    }

    /**
     * Search lead results.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchLead()
    {
        $term = request('query');

        $results = $this->leadRepository->scopeQuery(function ($query) use ($term) {
            return $query->where('title', 'like', '%' . $term . '%');
        })->paginate(10);

        return response()->json($results);
    }

    /**
     * Resquest client's registration via WhatsApp
     */
    public function requestRegistration($id)
    {
        try {
            $processo = $this->processoRepository->with('person')->findOrFail($id);
            
            // 2. Verificar Telefone
            $phone = null;
            if ($processo && $processo->person) {
                $contactNumbers = collect($processo->person->contact_numbers);
                $phoneData = $contactNumbers->first();
                $phone = is_object($phoneData) ? $phoneData->value : ($phoneData['value'] ?? null);
            }

            if (!$phone) {
                session()->flash('error', 'O cliente não possui telefone cadastrado.');
                return redirect()->back();
            }

            // 3. Carregar Template
            $templateMsg = core()->getConfigData('lawfirm.whatsapp_templates.messages.registration_request');
            if (empty($templateMsg)) {
                $templateMsg = "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que atualize suas informações cadastrais.\nUtilize o link: {link_portal}";
            }

            // 4. Substituir Variáveis
            $portalLink = route('lawfirm.public.portal.index', [
                'id' => $processo->id, 
                'token' => hash_hmac('sha256', $processo->id, config('app.key'))
            ]);

            $msg = str_replace(
                ['{cliente_nome}', '{processo_titulo}', '{link_portal}'],
                [
                    $processo->person->name,
                    $processo->titulo ?? 'Processo',
                    $portalLink
                ],
                $templateMsg
            );

            // 5. Enviar via Service
            $evolutionService = app(\SuiteZap\LawFirm\Whatsapp\Services\EvolutionService::class);
            $config = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getEvolutionConfig();

            if (!$config || empty($config['instance'])) {
                session()->flash('warning', 'Mensagem não enviada. WhatsApp não está configurado para o escritório.');
            } else {
                $evolutionService->sendMessage($config['instance'], $phone, $msg);
                session()->flash('success', 'Solicitação de cadastro enviada via WhatsApp!');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao enviar solicitação de cadastro: " . $e->getMessage());
            session()->flash('error', 'Erro ao enviar mensagem: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Request pending documents via WhatsApp
     */
    public function requestDocuments($id)
    {
        try {
            $processo = $this->processoRepository->with(['person', 'documents'])->findOrFail($id);
            
            // 2. Verificar Telefone
            $phone = null;
            if ($processo && $processo->person) {
                $contactNumbers = collect($processo->person->contact_numbers);
                $phoneData = $contactNumbers->first();
                $phone = is_object($phoneData) ? $phoneData->value : ($phoneData['value'] ?? null);
            }

            if (!$phone) {
                session()->flash('error', 'O cliente não possui telefone cadastrado.');
                return redirect()->back();
            }

            // Filtrar apenas documentos pendentes
            $pendingDocs = $processo->documents->where('status', 'pending');
            
            if ($pendingDocs->isEmpty()) {
                session()->flash('warning', 'Não há documentos pendentes para solicitar.');
                return redirect()->back();
            }

            $docsList = "";
            foreach ($pendingDocs as $doc) {
                $docsList .= "- " . $doc->name . "\n";
            }

            // 3. Carregar Template
            $templateMsg = core()->getConfigData('lawfirm.whatsapp_templates.messages.documents_request');
            if (empty($templateMsg)) {
                $templateMsg = "Olá {cliente_nome}. Referente ao processo {processo_titulo}, por favor, nos envie os seguintes documentos pendentes:\n\n{documentos_pendentes}\n\nVocê pode enviá-los por aqui mesmo ou através do nosso portal: {link_portal}";
            }

            // 4. Substituir Variáveis
            $portalLink = route('lawfirm.public.portal.index', [
                'id' => $processo->id, 
                'token' => hash_hmac('sha256', $processo->id, config('app.key'))
            ]);

            $msg = str_replace(
                ['{cliente_nome}', '{processo_titulo}', '{documentos_pendentes}', '{link_portal}'],
                [
                    $processo->person->name,
                    $processo->titulo ?? 'Processo',
                    $docsList,
                    $portalLink
                ],
                $templateMsg
            );

            // 5. Enviar via Service
            $evolutionService = app(\SuiteZap\LawFirm\Whatsapp\Services\EvolutionService::class);
            $config = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getEvolutionConfig();

            if (!$config || empty($config['instance'])) {
                session()->flash('warning', 'Mensagem não enviada. WhatsApp não está configurado para o escritório.');
            } else {
                $evolutionService->sendMessage($config['instance'], $phone, $msg);
                session()->flash('success', 'Solicitação de documentos pendentes enviada via WhatsApp!');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao enviar solicitação de documentos: " . $e->getMessage());
            session()->flash('error', 'Erro ao enviar mensagem: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}
