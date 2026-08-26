<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use SuiteZap\LawFirm\Legal\DataGrids\PrazoDataGrid;
use SuiteZap\LawFirm\Legal\Events\PrazoCreated;
use SuiteZap\LawFirm\Legal\Models\Prazo;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;
use Webkul\Admin\Http\Controllers\Controller;

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
     * @return RedirectResponse
     */
    public function notifyClient($id)
    {
        try {
            // 1. Buscar o Prazo
            $prazo = Prazo::with(['processo.person'])->findOrFail($id);
            $processo = $prazo->processo;

            if (! $processo || ! $processo->person) {
                session()->flash('error', 'Este prazo não tem um processo/pessoa vinculada.');

                return redirect()->back();
            }

            $person = $processo->person;

            // 2. Validar Pessoa e Telefone
            // contact_numbers is an array cast, not a relationship
            $contactNumbers = collect($person->contact_numbers);
            $phoneData = $contactNumbers->first();

            if (! $phoneData) {
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
            $config = MotherShipService::getEvolutionConfig();
            $instanceName = $config['instance'] ?? null;

            if (empty($instanceName)) {
                Log::warning('Disparo de Prazo ignorado: Instância Evolution não configurada.');
                session()->flash('warning', 'Mensagem não enviada: WhatsApp não configurado para seu escritório.');

                return redirect()->back();
            }

            $this->evolutionService->sendMessage($instanceName, $phone, $msg);

            session()->flash('success', 'Notificação enviada com sucesso para '.$person->name);

        } catch (\Exception $e) {
            Log::error('Erro ao notificar prazo: '.$e->getMessage());
            session()->flash('error', 'Erro ao enviar mensagem: '.$e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Toggle WhatsApp automated notifications (Robô Agendador) for a Prazo.
     *
     * @param  int  $id
     * @return JsonResponse|RedirectResponse
     */
    public function toggleNotify($id)
    {
        try {
            $prazo = Prazo::findOrFail($id);
            $prazo->update(['notificar_whatsapp' => ! $prazo->notificar_whatsapp]);

            $status = $prazo->notificar_whatsapp ? 'ativado' : 'desativado';
            $msg = "Robô Agendador {$status} para o prazo: {$prazo->titulo}";

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success'            => true,
                    'message'            => $msg,
                    'notificar_whatsapp' => $prazo->notificar_whatsapp,
                ]);
            }

            session()->flash('success', $msg);
        } catch (\Exception $e) {
            Log::error('Erro ao fazer toggle do prazo: '.$e->getMessage());
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            session()->flash('error', 'Erro ao alterar notificação: '.$e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Display a listing of the resource.
     *
     * @return View|JsonResponse
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
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.prazos.create'), 401, 'This action is unauthorized');

        \Log::info('PrazoController: store method hit.');
        $request->validate([
            'processo_id'     => 'required|exists:processos,id',
            'titulo'          => 'required|string|max:255',
            'data_vencimento' => 'required|date',
            'tipo'            => 'required|in:prazo,tarefa',
            'descricao'       => 'nullable|string',
        ]);

        $prazo = Prazo::create([
            'processo_id'     => $request->processo_id,
            'titulo'          => $request->titulo,
            'data_vencimento' => $request->data_vencimento,
            'tipo'            => $request->tipo,
            'descricao'       => $request->descricao,
            'status'          => 'pendente',
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
     * @return View
     */
    public function edit($id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.prazos.edit'), 401, 'This action is unauthorized');

        $prazo = Prazo::findOrFail($id);

        return view('lawfirm::admin.prazos.edit', compact('prazo'));
    }

    /**
     * Update the specified deadline in storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.prazos.edit'), 401, 'This action is unauthorized');

        $prazo = Prazo::findOrFail($id);

        $request->validate([
            'titulo'          => 'required|string|max:255',
            'data_vencimento' => 'required|date',
            'tipo'            => 'required|in:prazo,tarefa',
            'status'          => 'required|in:pendente,concluido',
            'descricao'       => 'nullable|string',
        ]);

        $prazo->update([
            'titulo'          => $request->titulo,
            'data_vencimento' => $request->data_vencimento,
            'tipo'            => $request->tipo,
            'status'          => $request->status,
            'descricao'       => $request->descricao,
            'concluido_em'    => $request->status === 'concluido' && $prazo->getOriginal('status') !== 'concluido' ? Carbon::now() : ($request->status === 'pendente' ? null : $prazo->concluido_em),
        ]);

        session()->flash('success', trans('lawfirm::app.processos.update-success'));

        return redirect()->route('admin.processos.edit', $prazo->processo_id);
    }

    /**
     * Mark the specified deadline as concluded.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function concluir($id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.prazos.edit'), 401, 'This action is unauthorized');

        $prazo = Prazo::findOrFail($id);

        $prazo->update([
            'status'       => 'concluido',
            'concluido_em' => Carbon::now(),
        ]);

        session()->flash('success', trans('lawfirm::app.prazos.conclude-success'));

        return back();
    }

    /**
     * Remove the specified deadline from storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.prazos.delete'), 401, 'This action is unauthorized');

        $prazo = Prazo::findOrFail($id);

        $prazo->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'message' => trans('lawfirm::app.prazos.delete-success'),
            ], 200);
        }

        session()->flash('success', trans('lawfirm::app.prazos.delete-success'));

        return back();
    }
}
