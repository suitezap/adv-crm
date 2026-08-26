<?php

namespace SuiteZap\LawFirm\Financial\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\Financial\Services\FinancialDashboardService;
use SuiteZap\LawFirm\Financial\Services\FinancialService;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use SuiteZap\LawFirm\TenantFinance\Services\TenantAsaasService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;
use Webkul\User\Models\User;

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
     * @var SaasFileService
     */
    protected $fileService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        FinancialDashboardService $dashboardService,
        FinancialService $financialService,
        SaasFileService $fileService
    ) {
        $this->dashboardService = $dashboardService;
        $this->financialService = $financialService;
        $this->fileService = $fileService;
    }

    /**
     * Display the financial dashboard.
     *
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        // Filtros (Datas e Responsável)
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Passa usuários para o filtro de responsável
        $users = User::all();

        // Obtém todas as métricas do Service
        $metrics = $this->dashboardService->getAllMetrics($startDate, $endDate);

        // ── Chart Data delegada ao Service (Sincronizada com ACL) ──
        $monthlyData = $this->dashboardService->getMonthlyTrend();
        $paymentDistribution = $this->dashboardService->getPaymentDistribution();

        return view('lawfirm::financial.index', [
            'totalReceitas'       => $metrics['totalReceitas'],
            'totalDespesas'       => $metrics['totalDespesas'],
            'saldoLiquido'        => $metrics['saldoLiquido'],
            'margemPercent'       => $metrics['margemPercent'],
            'pendenteReceber'     => $metrics['pendenteReceber'],
            'collectionRate'      => $metrics['collectionRate'],
            'dso'                 => $metrics['dso'],
            'aging'               => $metrics['aging'],
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'users'               => $users,
            'monthlyData'         => $monthlyData,
            'paymentDistribution' => $paymentDistribution,
        ]);
    }

    /**
     * Realiza a baixa rápida (Quick Pay) de um lançamento financeiro.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function quickPay(Request $request, $id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.financeiro.edit'), 401, 'This action is unauthorized');

        $validated = $request->validate([
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $financial = $this->financialService->quickPay(
            $id,
            $validated['payment_date'],
            $validated['payment_method']
        );

        return response()->json([
            'message' => 'Baixa realizada com sucesso!',
            'data'    => $financial,
        ]);
    }

    /**
     * Gera um recibo em PDF para um lançamento financeiro pago.
     * S3 Compatible: Converte logo para base64 data URI.
     *
     * @param  int  $id
     * @return Response
     */
    public function downloadReceipt($id)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.financeiro.view'), 401, 'This action is unauthorized');

        $transaction = Financial::with('processo.person')->findOrFail($id);

        // 1. Busca as Configurações Globais do Escritório
        $companyName = core()->getConfigData('lawfirm.settings.general.company_name') ?? 'Escritório de Advocacia';
        $logoPath = core()->getConfigData('lawfirm.settings.general.logo');
        $whatsapp = core()->getConfigData('lawfirm.settings.general.contact_whatsapp');
        $email = core()->getConfigData('lawfirm.settings.general.contact_email');
        $address = core()->getConfigData('lawfirm.settings.general.address');
        $website = core()->getConfigData('lawfirm.settings.general.website');

        // 2. Tratamento da Logo para PDF - S3 Compatible (Base64 Data URI)
        // ✅ COMPLIANCE: usa SaasFileService para garantir acesso ao bucket correto do Tenant.
        $logoBase64 = null;
        if ($logoPath && $this->fileService->exists($logoPath)) {
            $logoContents = $this->fileService->get($logoPath);
            $mimeType = $this->fileService->mimeType($logoPath) ?? 'image/png';
            if ($logoContents) {
                $logoBase64 = 'data:'.$mimeType.';base64,'.base64_encode($logoContents);
            }
        }

        // 3. Envia tudo para a View
        $pdf = Pdf::loadView('lawfirm::financial.pdf.receipt', compact(
            'transaction',
            'companyName',
            'logoBase64',
            'whatsapp',
            'email',
            'address',
            'website'
        ));

        return $pdf->download('recibo_'.$transaction->id.'.pdf');
    }

    /**
     * Store or update financials for a specific process.
     *
     * @param  int  $processId
     * @return RedirectResponse|JsonResponse
     */
    public function storeProcessFinancials(Request $request, $processId)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.financeiro.create'), 401, 'This action is unauthorized');

        $validated = $request->validate([
            'financeiros'                       => 'nullable|array',
            'financeiros.*.id'                  => 'nullable|integer',
            'financeiros.*.tipo'                => 'required|in:receita,despesa',
            'financeiros.*.nome'                => 'required|string|max:255',
            'financeiros.*.valor'               => 'required|numeric',
            'financeiros.*.data_vencimento'     => 'required|date',
            'financeiros.*.status'              => 'required|in:pendente,pago,cancelado',
            'financeiros.*.category'            => 'nullable|string|max:50',
            'financeiros.*.issued_at'           => 'nullable|date',
            'financeiros.*.payment_method'      => 'nullable|string|max:50',
            'financeiros.*.payment_date'        => 'nullable|date',
            'financeiros.*.parcelar'            => 'nullable|boolean',
            'financeiros.*.parcelas_qtd'        => 'nullable|integer|min:2|max:60',
            'financeiros.*.parcelas_frequencia' => 'nullable|integer',
            'financeiros.*.emit_asaas'          => 'nullable|boolean',
        ]);

        $processo = Processo::with('person')->findOrFail($processId);

        $this->financialService->syncFinancials($processo, $request->input('financeiros', []));

        // ── Asaas Integration: emit invoices for items marked with emit_asaas ──
        $asaasService = app(TenantAsaasService::class);

        if ($asaasService->isConfigured()) {
            $financeiros = $request->input('financeiros', []);

            foreach ($financeiros as $item) {
                if (empty($item['emit_asaas'])) {
                    continue;
                }
                if ($item['tipo'] !== 'receita') {
                    continue;
                }
                if (empty($item['payment_method'])) {
                    continue;
                }

                // Map payment_method to Asaas billing_type
                $billingTypeMap = [
                    'pix'    => 'PIX',
                    'boleto' => 'BOLETO',
                    'cartao' => 'CREDIT_CARD',
                ];
                $billingType = $billingTypeMap[$item['payment_method']] ?? null;
                if (! $billingType) {
                    continue;
                }

                // Resolve person data for customer creation
                $person = $processo->person;
                if (! $person) {
                    Log::warning('[FinancialAsaas] Processo sem contato vinculado, pulando Asaas.', ['processo_id' => $processId]);

                    continue;
                }

                // Fetch CPF/CNPJ from person_details or organization_details
                $cpfCnpj = '';
                $personDetail = DB::table('law_person_details')
                    ->where('person_id', $person->id)
                    ->first();
                if ($personDetail && ! empty($personDetail->cpf)) {
                    $cpfCnpj = preg_replace('/\D/', '', $personDetail->cpf);
                } else {
                    $orgDetail = DB::table('law_organization_details')
                        ->where('organization_id', $person->organization_id ?? 0)
                        ->first();
                    if ($orgDetail && ! empty($orgDetail->cnpj)) {
                        $cpfCnpj = preg_replace('/\D/', '', $orgDetail->cnpj);
                    }
                }

                try {
                    $invoice = $asaasService->createInvoice([
                        'person_id'   => $person->id,
                        'person_data' => [
                            'name'    => $person->name,
                            'cpfCnpj' => $cpfCnpj,
                            'email'   => collect($person->emails ?? [])->pluck('value')->first(),
                            'phone'   => collect($person->contact_numbers ?? [])->pluck('value')->first(),
                        ],
                        'processo_id'  => $processId,
                        'type'         => 'single',
                        'value'        => (float) $item['valor'],
                        'due_date'     => $item['data_vencimento'],
                        'billing_type' => $billingType,
                        'description'  => $item['nome'] ?? 'Cobrança do Processo #'.$processId,
                    ]);

                    if ($invoice) {
                        Log::info('[FinancialAsaas] Cobrança criada com sucesso.', [
                            'invoice_id'       => $invoice->id,
                            'asaas_payment_id' => $invoice->asaas_payment_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('[FinancialAsaas] Erro ao criar cobrança Asaas: '.$e->getMessage(), [
                        'processo_id' => $processId,
                        'item'        => $item['nome'] ?? 'N/A',
                    ]);
                }
            }
        }

        session()->flash('success', 'Financeiro atualizado com sucesso!');

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Financeiro atualizado com sucesso!',
                'data'    => $processo->financeiros,
            ]);
        }

        return redirect()->back();
    }

    /**
     * Sends a direct WhatsApp message to the client for billing purposes.
     *
     * Delegates message composition to FinancialService::prepareBillingWhatsapp()
     * and instance resolution to MotherShipService (Zero .env compliance).
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function sendWhatsappBilling($id, EvolutionService $evolutionService)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.financeiro.edit'), 401, 'This action is unauthorized');

        try {
            $financial = Financial::with(['processo.person'])->findOrFail($id);

            // Delega toda a lógica de composição para o Service (Skinny Controller)
            $billing = $this->financialService->prepareBillingWhatsapp($financial);

            // ✅ COMPLIANCE (Zero .env): lê instância EXCLUSIVAMENTE do MotherShip
            $evolutionConfig = MotherShipService::getEvolutionConfig();
            $instanceName = $evolutionConfig['instance'] ?? null;

            if (empty($instanceName)) {
                Log::error('Financial ZAP: Instância Evolution não configurada no MotherShip para este Tenant. Verifique infrastructure_nodes (type=evolution).');

                return response()->json([
                    'success'  => false,
                    'message'  => 'WhatsApp não configurado para este escritório. Contate o suporte.',
                ], 503);
            }

            Log::info("Financial ZAP: Sending to {$billing['phone']} via instance {$instanceName}");

            $result = $evolutionService->sendMessage($instanceName, $billing['phone'], $billing['message']);

            if (isset($result['error']) || (is_array($result) && isset($result['status']) && $result['status'] >= 400)) {
                Log::error('Evolution API Error', ['result' => $result]);

                return response()->json(['success' => false, 'message' => 'Erro da API do WhatsApp. Verifique se o aparelho está conectado.'], 500);
            }

            return response()->json(['success' => true, 'message' => 'Cobrança enviada com sucesso!']);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('Financial ZAP Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['success' => false, 'message' => 'Erro interno ao processar o envio.'], 500);
        }
    }
}
