<?php

namespace SuiteZap\LawFirm\Escavador\Http\Controllers\Admin;

use SuiteZap\LawFirm\Escavador\DataGrids\EscavadorHistoryDataGrid;
use SuiteZap\LawFirm\Escavador\Models\EscavadorRequest;
use Webkul\Admin\Http\Controllers\Controller;

class EscavadorHistoryController extends Controller
{
    public function index()
    {
        abort_if(! bouncer()->hasPermission('lawfirm.escavador.view'), 401, 'This action is unauthorized');

        if (request()->ajax()) {
            return app(EscavadorHistoryDataGrid::class)->toJson();
        }

        return view('lawfirm::admin.escavador.history.index');
    }

    public function show($id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.escavador.view'), 401, 'This action is unauthorized');

        // TenantScope global restringe ao tenant da sessão (PRIV-AUDIT-001).
        $history = EscavadorRequest::with('processo')->findOrFail($id);

        return view('lawfirm::admin.escavador.history.show', compact('history'));
    }
}
