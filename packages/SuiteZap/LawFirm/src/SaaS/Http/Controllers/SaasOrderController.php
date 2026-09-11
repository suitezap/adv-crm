<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\SaaS\DataGrids\SaasOrdersDataGrid;

/**
 * SaasOrderController — Skinny Controller
 *
 * Exibe o histórico de pedidos (intenções de compra) do tenant.
 * O DataGrid faz todo o trabalho pesado.
 */
class SaasOrderController extends Controller
{
    /**
     * Exibe a lista de pedidos do tenant.
     * GET admin/juridico/orders
     */
    public function index()
    {
        abort_if(! bouncer()->hasPermission('lawfirm.saas.manage'), 401, 'This action is unauthorized');

        if (request()->ajax()) {
            return app(SaasOrdersDataGrid::class)->toJson();
        }

        return view('lawfirm::subscription.orders');
    }
}
