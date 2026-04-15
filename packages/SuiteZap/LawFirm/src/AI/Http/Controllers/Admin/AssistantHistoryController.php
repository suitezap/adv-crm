<?php

namespace SuiteZap\LawFirm\AI\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\DataGrids\AssistantHistoryDataGrid;

class AssistantHistoryController extends Controller
{
    /**
     * View history datagrid
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(AssistantHistoryDataGrid::class)->toJson();
        }

        return view('lawfirm::admin.assistants.history.index');
    }

    /**
     * Show details of a specific AI execution
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $history = AssistantHistory::with(['template', 'user', 'lead'])->findOrFail($id);

        return view('lawfirm::admin.assistants.history.show', compact('history'));
    }
}
