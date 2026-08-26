<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers\Admin;

use Illuminate\View\View;
use SuiteZap\LawFirm\SaaS\Services\SaasStorageService;
use Webkul\Admin\Http\Controllers\Controller;

class SaasDashboardController extends Controller
{
    /**
     * @var SaasStorageService
     */
    protected $storageService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SaasStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Display the SaaS subscription dashboard.
     *
     * @return View
     */
    public function index()
    {
        // Get storage summary from service
        $storageSummary = $this->storageService->getSummary();

        // Get AI credits settings
        $aiCredits = core()->getConfigData('lawfirm.saas.ai.credits') ?? 0;
        $planType = core()->getConfigData('lawfirm.saas.ai.plan_type') ?? 'prepaid';

        // Mock subscription data (can be expanded later with real billing integration)
        $subscription = [
            'status'       => 'Ativo', // Mock
            'plan_name'    => 'LawFirm Pro', // Mock
            'expires_at'   => now()->addMonths(6)->format('d/m/Y'), // Mock
            'status_color' => 'success', // success, warning, danger
        ];

        // Storage Alert
        $storageAlert = false;
        if ($storageSummary['percent'] >= 90) {
            $storageAlert = true;
        }

        return view('lawfirm::admin.saas.index', compact(
            'storageSummary',
            'aiCredits',
            'planType',
            'subscription',
            'storageAlert'
        ));
    }
}
