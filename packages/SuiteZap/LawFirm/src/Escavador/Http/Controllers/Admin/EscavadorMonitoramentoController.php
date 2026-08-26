<?php

namespace SuiteZap\LawFirm\Escavador\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use SuiteZap\LawFirm\Escavador\DataGrids\EscavadorMonitoramentoDataGrid;
use SuiteZap\LawFirm\Escavador\Models\EscavadorMonitoramento;
use Webkul\Admin\Http\Controllers\Controller;

class EscavadorMonitoramentoController extends Controller
{
    /**
     * Display a listing of monitoramentos.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(EscavadorMonitoramentoDataGrid::class)->toJson();
        }

        return view('lawfirm::admin.escavador.monitoramentos.index');
    }

    /**
     * Show the form for creating a new monitoramento.
     *
     * @return View
     */
    public function create()
    {
        return view('lawfirm::admin.escavador.monitoramentos.create');
    }

    /**
     * Toggle WhatsApp notification preference for a monitoring.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function toggleWhatsapp($id)
    {
        $monitoramento = EscavadorMonitoramento::findOrFail($id);

        $monitoramento->notify_whatsapp = ! $monitoramento->notify_whatsapp;
        $monitoramento->save();

        return response()->json([
            'status'          => 'success',
            'message'         => 'Status de notificação por WhatsApp atualizado com sucesso!',
            'notify_whatsapp' => $monitoramento->notify_whatsapp,
        ]);
    }
}
