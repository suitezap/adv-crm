<?php

namespace SuiteZap\LawFirm\Financial\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use SuiteZap\LawFirm\Financial\DataGrids\FinancialDataGrid;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\Financial\Services\FinancialDashboardService;
use SuiteZap\LawFirm\Financial\Services\FinancialService;

class FinancialController extends Controller
{
    /**
     * @var FinancialDashboardService
     */
    protected $dashboardService;

    /**
     * @var FinancialService
     */
    protected $financialService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        FinancialDashboardService $dashboardService,
        FinancialService $financialService
    ) {
        $this->dashboardService = $dashboardService;
        $this->financialService = $financialService;
    }

    /**
     * Display the financial dashboard.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Handle AJAX request for DataGrid
        if ($request->ajax()) {
            return app(FinancialDataGrid::class)->process();
        }

        // Filtros (Datas e Responsável)
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Passa usuários para o filtro de responsável
        $users = \Webkul\User\Models\User::all();

        // Obtém todas as métricas do Service
        $metrics = $this->dashboardService->getAllMetrics($startDate, $endDate);

        return view('lawfirm::financial.index', [
            'totalReceitas' => $metrics['totalReceitas'],
            'totalDespesas' => $metrics['totalDespesas'],
            'saldoLiquido' => $metrics['saldoLiquido'],
            'margemPercent' => $metrics['margemPercent'],
            'pendenteReceber' => $metrics['pendenteReceber'],
            'collectionRate' => $metrics['collectionRate'],
            'dso' => $metrics['dso'],
            'aging' => $metrics['aging'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'users' => $users,
        ]);
    }

    /**
     * Realiza a baixa rápida (Quick Pay) de um lançamento financeiro.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickPay(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $financial = $this->financialService->quickPay(
            $id,
            $validated['payment_date'],
            $validated['payment_method']
        );

        return response()->json([
            'message' => 'Baixa realizada com sucesso!',
            'data' => $financial,
        ]);
    }

    /**
     * Gera um recibo em PDF para um lançamento financeiro pago.
     * S3 Compatible: Converte logo para base64 data URI.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadReceipt($id)
    {
        $transaction = \SuiteZap\LawFirm\Models\Financial::with('processo.person')->findOrFail($id);

        // 1. Busca as Configurações Globais do Escritório
        $companyName = core()->getConfigData('lawfirm.settings.general.company_name') ?? 'Escritório de Advocacia';
        $logoPath = core()->getConfigData('lawfirm.settings.general.logo');
        $whatsapp = core()->getConfigData('lawfirm.settings.general.contact_whatsapp');
        $email = core()->getConfigData('lawfirm.settings.general.contact_email');
        $address = core()->getConfigData('lawfirm.settings.general.address');
        $website = core()->getConfigData('lawfirm.settings.general.website');

        // 2. Tratamento da Logo para PDF - S3 Compatible (Base64 Data URI)
        $logoBase64 = null;
        if ($logoPath && Storage::exists($logoPath)) {
            $logoContents = Storage::get($logoPath);
            $mimeType = Storage::mimeType($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContents);
        }

        // 3. Envia tudo para a View
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('lawfirm::financial.pdf.receipt', compact(
            'transaction',
            'companyName',
            'logoBase64',
            'whatsapp',
            'email',
            'address',
            'website'
        ));

        return $pdf->download('recibo_' . $transaction->id . '.pdf');
    }
    /**
     * Store or update financials for a specific process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $processId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeProcessFinancials(Request $request, $processId)
    {
        $validated = $request->validate([
            'financeiros' => 'nullable|array',
            'financeiros.*.id' => 'nullable|integer',
            'financeiros.*.tipo' => 'required|in:receita,despesa',
            'financeiros.*.nome' => 'required|string|max:255',
            'financeiros.*.valor' => 'required|numeric',
            'financeiros.*.data_vencimento' => 'required|date',
            'financeiros.*.status' => 'required|in:pendente,pago,cancelado',
            'financeiros.*.category' => 'nullable|string|max:50',
            'financeiros.*.issued_at' => 'nullable|date',
            'financeiros.*.payment_method' => 'nullable|string|max:50',
            'financeiros.*.payment_date' => 'nullable|date',
            'financeiros.*.parcelar' => 'nullable|boolean',
            'financeiros.*.parcelas_qtd' => 'nullable|integer|min:2|max:60',
            'financeiros.*.parcelas_frequencia' => 'nullable|integer',
        ]);

        $processo = \SuiteZap\LawFirm\Legal\Models\Processo::findOrFail($processId);

        $this->financialService->syncFinancials($processo, $request->input('financeiros', []));

        session()->flash('success', 'Financeiro atualizado com sucesso!');

        return redirect()->back();
    }
}
