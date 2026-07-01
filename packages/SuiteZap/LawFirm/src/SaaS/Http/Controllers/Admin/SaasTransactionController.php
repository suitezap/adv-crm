<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers\Admin;

use SuiteZap\LawFirm\SaaS\DataGrids\SaasAdditionsDataGrid;
use SuiteZap\LawFirm\SaaS\DataGrids\SaasTransactionsDataGrid;
use Webkul\Admin\Http\Controllers\Controller;

class SaasTransactionController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return app(SaasTransactionsDataGrid::class)->toJson();
        }

        return view('lawfirm::admin.saas.transactions');
    }

    public function additions()
    {
        if (request()->ajax()) {
            return app(SaasAdditionsDataGrid::class)->toJson();
        }

        return redirect()->route('lawfirm.saas.transactions');
    }
}
