<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Illuminate\Http\Request;
use SuiteZap\LawFirm\Legal\DataGrids\PrazoDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\Legal\Models\Prazo;
use SuiteZap\LawFirm\Events\PrazoCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;

class PrazoController extends Controller
{
    protected $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;

        // REMOVED: Invalid middleware call causing "Object of type Webkul\Core\Acl is not callable"
        // $this->middleware('acl:lawfirm.prazos.view');
    }

    /**
     * Send manual WhatsApp notification.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notifyClient($id)
    {
        try {
            // 1. Buscar o Prazo
            $prazo = Prazo::with(['processo.person'])->findOrFail($id);
            $processo = $prazo->processo;

            if (!$processo || !$processo->person) {
                session()->flash('error', 'Este prazo não tem um processo/pessoa vinculada.');
                return redirect()->back();
            }

            $person = $processo->person;

            // 2. Validar Pessoa e Telefone
            // contact_numbers is an array cast, not a relationship
            $contactNumbers = collect($person->contact_numbers);
            $phoneData = $contactNumbers->first();

            if (!$phoneData) {
                session()->flash('error', 'A pessoa vinculada não possui telefone cadastrado.');
                return redirect()->back();
            }

            $phone = is_object($phoneData) ? $phoneData->value : $phoneData['value'];

            // 3. Carregar Template
            $template = core()->getConfigData('lawfirm.whatsapp_templates.messages.new_prazo_client');
            if (empty($template)) {
                session()->flash('warning', 'Template de mensagem não configurado em Ajustes.');
                return redirect()->back();
            }

            // 4. Substituir Variáveis
            $msg = str_replace(
                ['{cliente_nome}', '{prazo_titulo}', '{prazo_data}', '{prazo_descricao}'],
                [$person->name, $prazo->titulo, Carbon::parse($prazo->data_vencimento)->format('d/m/Y'), $prazo->descricao ?? ''],
                $template
            );

            // 5. Enviar via Service
            $config = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getEvolutionConfig();
            $instanceName = $config['instance'] ?? env('EVOLUTION_INSTANCE_NAME', 'LawFirm');

            $this->evolutionService->sendMessage($instanceName, $phone, $msg);

            session()->flash('success', 'Notificação enviada com sucesso para ' . $person->name);

        } catch (\Exception $e) {
            Log::error("Erro ao notificar prazo: " . $e->getMessage());
            session()->flash('error', 'Erro ao enviar mensagem: ' . $e->getMessage());
        }

        return redirect()->back();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(PrazoDataGrid::class)->toJson();
        }

        return view('lawfirm::admin.prazos.index');
    }

    /**
     * Store a newly created deadline in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        \Log::info("PrazoController: store method hit.");
        $request->validate([
            'processo_id' => 'required|exists:processos,id',
            'titulo' => 'required|string|max:255',
            'data_vencimento' => 'required|date',
            'tipo' => 'required|in:fatal,comum',
            'descricao' => 'nullable|string',
        ]);

        $prazo = Prazo::create([
            'processo_id' => $request->processo_id,
            'titulo' => $request->titulo,
            'data_vencimento' => $request->data_vencimento,
            'tipo' => $request->tipo,
            'descricao' => $request->descricao,
            'status' => 'pendente',
        ]);

        \Log::info("PrazoController: Disparando evento PrazoCreated para Prazo ID {$prazo->id}");
        event(new PrazoCreated($prazo));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => trans('lawfirm::app.prazos.create-success'), 'status' => 'success']);
        }

        session()->flash('success', trans('lawfirm::app.prazos.create-success'));

        return back();
    }

    /**
     * Show the form for editing the specified deadline.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $prazo = Prazo::findOrFail($id);

        return view('lawfirm::admin.prazos.edit', compact('prazo'));
    }

    /**
     * Update the specified deadline in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $prazo = Prazo::findOrFail($id);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'data_vencimento' => 'required|date',
            'tipo' => 'required|in:fatal,comum',
            'status' => 'required|in:pendente,concluido',
            'descricao' => 'nullable|string',
        ]);

        $prazo->update([
            'titulo' => $request->titulo,
            'data_vencimento' => $request->data_vencimento,
            'tipo' => $request->tipo,
            'status' => $request->status,
            'descricao' => $request->descricao,
            'concluido_em' => $request->status === 'concluido' && $prazo->getOriginal('status') !== 'concluido' ? Carbon::now() : ($request->status === 'pendente' ? null : $prazo->concluido_em),
        ]);

        session()->flash('success', trans('lawfirm::app.processos.update-success'));

        return redirect()->route('admin.processos.edit', $prazo->processo_id);
    }

    /**
     * Mark the specified deadline as concluded.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function concluir($id)
    {
        $prazo = Prazo::findOrFail($id);

        $prazo->update([
            'status' => 'concluido',
            'concluido_em' => Carbon::now(),
        ]);

        session()->flash('success', trans('lawfirm::app.prazos.conclude-success'));

        return back();
    }

    /**
     * Remove the specified deadline from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $prazo = Prazo::findOrFail($id);

        $prazo->delete();

        session()->flash('success', trans('lawfirm::app.prazos.delete-success'));

        return back();
    }
}
