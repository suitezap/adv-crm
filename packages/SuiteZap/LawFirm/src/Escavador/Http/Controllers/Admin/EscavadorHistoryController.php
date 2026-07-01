<?php

namespace SuiteZap\LawFirm\Escavador\Http\Controllers\Admin;

use SuiteZap\LawFirm\Escavador\DataGrids\EscavadorHistoryDataGrid;
use SuiteZap\LawFirm\Escavador\Models\EscavadorRequest;
use Webkul\Admin\Http\Controllers\Controller;

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
