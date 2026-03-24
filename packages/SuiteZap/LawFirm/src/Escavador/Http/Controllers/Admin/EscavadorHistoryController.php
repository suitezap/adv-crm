<?php

namespace SuiteZap\LawFirm\Escavador\Http\Controllers\Admin;

use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\Escavador\DataGrids\EscavadorHistoryDataGrid;
use SuiteZap\LawFirm\Escavador\Models\EscavadorRequest;

class EscavadorHistoryController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return app(EscavadorHistoryDataGrid::class)->toJson();
        }

        return view('lawfirm::admin.escavador.history.index');
    }

    public function show($id)
    {
        $history = EscavadorRequest::with('processo')->findOrFail($id);

        return view('lawfirm::admin.escavador.history.show', compact('history'));
    }
}
