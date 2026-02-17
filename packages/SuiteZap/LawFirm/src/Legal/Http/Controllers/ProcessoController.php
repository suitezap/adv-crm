<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use SuiteZap\LawFirm\Legal\DataGrids\ProcessoDataGrid;
use SuiteZap\LawFirm\Legal\Repositories\ProcessoRepository;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;

use SuiteZap\LawFirm\Rules\ValidarCNJ;
use SuiteZap\LawFirm\Rules\ValidarCpfCnpj;
use SuiteZap\LawFirm\GED\Services\DocumentService;
use SuiteZap\LawFirm\Legal\Services\DeadlineService;
use SuiteZap\LawFirm\Financial\Services\FinancialService;

class ProcessoController extends Controller
{
    /**
     * ProcessoRepository object
     *
     * @var \SuiteZap\LawFirm\Repositories\ProcessoRepository
     */
    protected $processoRepository;

    /**
     * PersonRepository object
     *
     * @var \Webkul\Contact\Repositories\PersonRepository
     */
    protected $personRepository;

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
        LeadRepository $leadRepository,
        ActivityRepository $activityRepository,
        DocumentService $documentService,
        DeadlineService $deadlineService,
        FinancialService $financialService
    ) {
        $this->processoRepository = $processoRepository;
        $this->personRepository = $personRepository;
        $this->leadRepository = $leadRepository;
        $this->activityRepository = $activityRepository;
        $this->documentService = $documentService;
        $this->deadlineService = $deadlineService;
        $this->financialService = $financialService;
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
        return app(\SuiteZap\LawFirm\DataGrids\LeadProcessosDataGrid::class)->process();
    }

    /**
     * Display a listing of the resource for a specific person.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function personProcessos($id)
    {
        return app(\SuiteZap\LawFirm\DataGrids\PersonProcessosDataGrid::class)->process();
    }

    /**
     * Display a listing of the resource for a specific organization.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function organizationProcessos($id)
    {
        return app(\SuiteZap\LawFirm\DataGrids\OrganizationProcessosDataGrid::class)->process();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        // DEBUG: Log uploaded files details before validation
        if (request()->hasFile('anexos')) {
            foreach (request()->file('anexos') as $file) {
                \Log::debug("STORE UPLOAD DEBUG: Name={$file->getClientOriginalName()} | Ext={$file->getClientOriginalExtension()} | MimeType={$file->getMimeType()} | GuessedExt={$file->guessExtension()} | Size={$file->getSize()}");
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
            'titulo' => 'required|string|max:255',
            'numero_cnj' => ['nullable', 'string', 'unique:processos,numero_cnj', new ValidarCNJ],
            'protocolo_distribuicao' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'person_id' => 'required|exists:persons,id',
            'lead_id' => 'nullable|exists:leads,id',
            'tribunal' => 'nullable|string|max:255',
            'comarca' => 'nullable|string|max:255',
            'vara' => 'nullable|string|max:255',
            'juiz_atual' => 'nullable|string|max:255',
            'link_acesso' => 'nullable|string|max:500',
            'fase_processual' => 'nullable|string|max:255',
            'parte_contraria' => 'nullable|string|max:255', // Legacy field, keeping for now or replacing usage
            'opposing_party_name' => 'nullable|string|max:255',
            'opposing_party_type' => 'nullable|in:PF,PJ',
            'opposing_party_document' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    $type = request('opposing_party_type');
                    if ($type === 'PF') {
                        $rule = new \SuiteZap\LawFirm\Rules\Cpf;
                        if (!$rule->passes($attribute, $value)) {
                            $fail('O CPF da parte contrária é inválido.');
                        }
                    } elseif ($type === 'PJ') {
                        $rule = new \SuiteZap\LawFirm\Rules\Cnpj;
                        if (!$rule->passes($attribute, $value)) {
                            $fail('O CNPJ da parte contrária é inválido.');
                        }
                    }
                }
            ],
            'advogado_parte_contraria' => 'nullable|string|max:255',
            'area_direito' => 'nullable|string|max:255',
            'probabilidade_exito' => 'nullable|string|max:255',
            'data_distribuicao' => 'nullable|date',
            'data_audiencia' => 'nullable|date',
            'valor_causa' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_parte' => 'nullable|in:autor,reu',
            'tipo_pessoa' => 'nullable|in:Física,Jurídica',
            'cpf_cnpj' => ['nullable', 'string', 'max:20', new ValidarCpfCnpj],
            'advogado_oab' => 'nullable|string|max:20',
            'whatsapp_advogado_contrario' => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'email_advogado_contrario' => 'nullable|email:rfc,dns|max:255',
            'subarea_direito' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'prazos.*.titulo' => 'required|string|max:255',
            'prazos.*.data_vencimento' => 'required|date',
            'prazos.*.status' => 'required|in:pendente,concluido',
            'prazos.*.descricao' => 'nullable|string',
            // Financials are now handled via AJAX in a separate component
            // 'financeiros.*.tipo' => 'required|in:receita,despesa',
            // ...
            // EXPANDED MIMES LIST: Added log, md, xml, odt, ods
            'anexos.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,txt,csv,xls,xlsx,rtf,log,md,xml,odt,ods|max:20480',
        ], [
            'whatsapp_advogado_contrario.regex' => 'O formato do WhatsApp é inválido. Use: (99) 99999-9999.',
            'prazos.*.titulo.required' => 'O título do prazo é obrigatório.',
            'prazos.*.data_vencimento.required' => 'A data de vencimento do prazo é obrigatória.',
            'anexos.*.mimes' => 'Tipo de arquivo não permitido. Aceitos: PDF, Imagens, Office, Texto (txt, log, md, csv).',
            'anexos.*.max' => 'O tamanho máximo do arquivo é 20MB.',
        ]);

        if ($validator->fails()) {
            \Log::warning("ProcessoController::store Validation Failed: " . json_encode($validator->errors()->toArray()));
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = request()->all();
        $data['person_id'] = !empty($data['person_id']) ? $data['person_id'] : null;
        $data['lead_id'] = !empty($data['lead_id']) ? $data['lead_id'] : null;
        $data['user_id'] = !empty($data['user_id']) ? $data['user_id'] : null;

        Event::dispatch('lawfirm.processo.create.before');

        $processo = $this->processoRepository->create($data);

        // CREATE PRAZOS (Delegated to DeadlineService)
        if (isset($data['prazos']) && is_array($data['prazos'])) {
            $this->deadlineService->syncDeadlines($processo, $data['prazos']);
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

        $this->logProcessHistory($processo, 'Criado');
        $this->ensureCalendarEvent($processo);

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

        return view('lawfirm::admin.processos.show', compact('processo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $processo = \SuiteZap\LawFirm\Legal\Models\Processo::with([
            'person',
            'lead',
            'responsavel',
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
    public function update($id)
    {
        // DEBUG: Log uploaded files details before validation
        if (request()->hasFile('anexos')) {
            foreach (request()->file('anexos') as $file) {
                \Log::debug("UPLOAD DEBUG: Name={$file->getClientOriginalName()} | Ext={$file->getClientOriginalExtension()} | MimeType={$file->getMimeType()} | GuessedExt={$file->guessExtension()} | Size={$file->getSize()}");
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
            'titulo' => 'required|string|max:255',
            'numero_cnj' => ['nullable', 'string', 'unique:processos,numero_cnj,' . $id, new ValidarCNJ],
            'status' => 'required|string|max:255',
            'person_id' => 'required|exists:persons,id',
            'lead_id' => 'nullable|exists:leads,id',
            'tribunal' => 'nullable|string|max:255',
            'comarca' => 'nullable|string|max:255',
            'vara' => 'nullable|string|max:255',
            'link_acesso' => 'nullable|string|max:500',
            'fase_processual' => 'nullable|string|max:255',
            'parte_contraria' => 'nullable|string|max:255',
            'opposing_party_name' => 'nullable|string|max:255',
            'opposing_party_type' => 'nullable|in:PF,PJ',
            'opposing_party_document' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    $type = request('opposing_party_type');
                    if ($type === 'PF') {
                        $rule = new \SuiteZap\LawFirm\Rules\Cpf;
                        if (!$rule->passes($attribute, $value)) {
                            $fail('O CPF da parte contrária é inválido.');
                        }
                    } elseif ($type === 'PJ') {
                        $rule = new \SuiteZap\LawFirm\Rules\Cnpj;
                        if (!$rule->passes($attribute, $value)) {
                            $fail('O CNPJ da parte contrária é inválido.');
                        }
                    }
                }
            ],
            'advogado_parte_contraria' => 'nullable|string|max:255',
            'area_direito' => 'nullable|string|max:255',
            'probabilidade_exito' => 'nullable|string|max:255',
            'data_distribuicao' => 'nullable|date',
            'data_audiencia' => 'nullable|date',
            'valor_causa' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_parte' => 'nullable|in:autor,reu',
            'tipo_pessoa' => 'nullable|in:Física,Jurídica',
            'cpf_cnpj' => ['nullable', 'string', 'max:20', new ValidarCpfCnpj],
            'advogado_oab' => 'nullable|string|max:20',
            'whatsapp_advogado_contrario' => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'email_advogado_contrario' => 'nullable|email:rfc,dns|max:255',
            'subarea_direito' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'prazos.*.titulo' => 'required|string|max:255',
            'prazos.*.data_vencimento' => 'required|date',
            'prazos.*.status' => 'required|in:pendente,concluido',
            'prazos.*.descricao' => 'nullable|string',
            // Financials are now handled via AJAX in a separate component
            // 'financeiros.*.tipo' => 'required|in:receita,despesa',
            // ...
            // EXPANDED MIMES LIST: Added log, md, xml, odt, ods
            'anexos.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,txt,csv,xls,xlsx,rtf,log,md,xml,odt,ods|max:20480',
        ], [
            'whatsapp_advogado_contrario.regex' => 'O formato do WhatsApp é inválido. Use: (99) 99999-9999.',
            'prazos.*.titulo.required' => 'O título do prazo é obrigatório.',
            'prazos.*.data_vencimento.required' => 'A data de vencimento do prazo é obrigatória.',
            'anexos.*.mimes' => 'Tipo de arquivo não permitido. Aceitos: PDF, Imagens, Office, Texto (txt, log, md, csv).',
            'anexos.*.max' => 'O tamanho máximo do arquivo é 20MB.',
        ]);

        if ($validator->fails()) {
            // Log validation errors for debugging
            \Log::warning("ProcessoController::update Validation Failed: " . json_encode($validator->errors()->toArray()));

            // Redirect back with errors
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = request()->all();
        $data['person_id'] = !empty($data['person_id']) ? $data['person_id'] : null;
        $data['lead_id'] = !empty($data['lead_id']) ? $data['lead_id'] : null;
        $data['user_id'] = !empty($data['user_id']) ? $data['user_id'] : null;

        Event::dispatch('lawfirm.processo.update.before', $id);

        $processo = $this->processoRepository->update($data, $id);

        // 1. Sincronizar Financeiro
        // 1. Sincronizar Financeiro (Handled by isolated component via AJAX)
        // if (request()->has('financials')) {
        //     $this->financialService->syncFinancials($processo, request('financials'));
        // } elseif (request()->has('financeiros')) {
        //     $this->financialService->syncFinancials($processo, request('financeiros'));
        // }

        // 2. Processar Uploads
        if (request()->hasFile('anexos') || request()->hasFile('anexo')) {
            $this->documentService->processUploads($processo, request());
        }

        // 3. Sincronizar Prazos
        if (request()->has('prazos')) {
            $this->deadlineService->syncDeadlines($processo, request('prazos'));
        }

        $this->logProcessHistory($processo, 'Atualizado');
        $this->ensureCalendarEvent($processo);

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
        try {
            Event::dispatch('lawfirm.processo.delete.before', $id);

            // Fetch process before deleting to clean up events
            $processo = $this->processoRepository->find($id);
            if ($processo) {
                $this->forceCleanupCalendarEvent($processo);
            }

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

                // Clean up calendar events
                $this->forceCleanupCalendarEvent($processo);

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
     * Ensure calendar event logic: Create, Update OR Delete based on state.
     * Uses [REF:PROC_ID:{id}] tag to strictly identify the event.
     *
     * @param mixed $processo
     * @return void
     */
    private function ensureCalendarEvent($processo)
    {
        $userId = auth()->guard('user')->id();
        $tag = "[REF:PROC_ID:{$processo->id}]";

        // 1. Find existing activity by TAG
        // We filter by lightweight attributes first: meeting + is_done=0 + user
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

        $data = [
            'type' => 'meeting',
            'title' => $title,
            'comment' => $comment,
            'schedule_from' => $scheduledFrom,
            'schedule_to' => $scheduledTo,
            'is_done' => 0,
            'user_id' => $userId,
            'participants' => [
                'users' => [$userId],
            ],
        ];

        if ($processo->person_id) {
            $data['participants']['persons'] = [$processo->person_id];
        }

        if ($processo->lead_id) {
            $data['lead_id'] = $processo->lead_id;
        }

        if ($existingActivity) {
            // Update existing
            $this->activityRepository->update($data, $existingActivity->id);
        } else {
            // Create new
            $this->activityRepository->create($data);
        }
    }

    /**
     * Helper to force cleanup on destroy
     *
     * @param mixed $processo
     * @return void
     */
    private function forceCleanupCalendarEvent($processo)
    {
        $userId = auth()->guard('user')->id();
        $tag = "[REF:PROC_ID:{$processo->id}]";

        $activities = $this->activityRepository->findWhere([
            'type' => 'meeting',
            'is_done' => 0,
            'user_id' => $userId
        ]);

        $existingActivity = $activities->first(function ($activity) use ($tag) {
            return str_contains($activity->comment, $tag);
        });

        if ($existingActivity) {
            $this->activityRepository->delete($existingActivity->id);
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
     * Log process history as a note.
     *
     * @param mixed $processo
     * @param string $acao
     * @return void
     */
    private function logProcessHistory($processo, $acao)
    {
        $now = Carbon::now();

        $data = [
            'type' => 'note',
            'title' => "Histórico ($acao)",
            'comment' => "Histórico ($acao): Processo atualizado. Status: " . $processo->status,
            'schedule_from' => $now,
            'schedule_to' => $now,
            'is_done' => 1,
            'user_id' => auth()->guard('user')->id(),
        ];

        if ($processo->lead_id) {
            $data['lead_id'] = $processo->lead_id;
        }

        $this->activityRepository->create($data);
    }
}
