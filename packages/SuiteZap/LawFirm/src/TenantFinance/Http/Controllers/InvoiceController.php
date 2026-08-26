<?php

namespace SuiteZap\LawFirm\TenantFinance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\Financial\DataGrids\FinancialDataGrid;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasCustomer;
use SuiteZap\LawFirm\TenantFinance\Models\TenantInvoice;
use SuiteZap\LawFirm\TenantFinance\Services\TenantAsaasService;

/**
 * InvoiceController
 *
 * CRUD de cobranças emitidas pelo escritório para seus clientes.
 * Skinny Controller — toda lógica de API no TenantAsaasService.
 */
class InvoiceController extends Controller
{
    protected TenantAsaasService $asaasService;

    public function __construct(TenantAsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    /**
     * Listagem de cobranças (DataGrid).
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(FinancialDataGrid::class)->process();
        }

        return view('lawfirm::TenantFinance.invoices.index');
    }

    /**
     * Detalhes de uma cobrança.
     */
    public function show(int $id)
    {
        $invoice = TenantInvoice::with(['customer', 'processo'])->findOrFail($id);

        return view('lawfirm::TenantFinance.invoices.show', compact('invoice'));
    }

    /**
     * Criar cobrança a partir do formulário / modal (AJAX POST).
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->asaasService->isConfigured()) {
            return response()->json([
                'error' => 'Configure seu Asaas em Configurações antes de criar cobranças.',
            ], 503);
        }

        $validated = $request->validate([
            'person_id'          => 'required|integer',
            'person_name'        => 'required|string|max:200',
            'person_cpf_cnpj'    => 'required|string|max:20',
            'person_email'       => 'nullable|email|max:200',
            'person_phone'       => 'nullable|string|max:30',
            'processo_id'        => 'nullable|integer',
            'financial_id'       => 'nullable|integer',
            'type'               => 'required|in:single,installment,subscription',
            'value'              => 'required|numeric|min:5',
            'due_date'           => 'required|date',
            'billing_type'       => 'required|in:BOLETO,PIX,CREDIT_CARD',
            'description'        => 'required|string|max:500',
            'installment_count'  => 'required_if:type,installment|nullable|integer|min:2|max:12',
            'cycle'              => 'required_if:type,subscription|nullable|in:MONTHLY,WEEKLY,BIWEEKLY,QUARTERLY,SEMIANNUALLY,YEARLY',
        ]);

        $invoice = $this->asaasService->createInvoice([
            'person_id'         => $validated['person_id'],
            'person_data'       => [
                'name'      => $validated['person_name'],
                'cpfCnpj'   => $validated['person_cpf_cnpj'],
                'email'     => $validated['person_email'] ?? null,
                'phone'     => $validated['person_phone'] ?? null,
            ],
            'processo_id'       => $validated['processo_id'] ?? null,
            'financial_id'      => $validated['financial_id'] ?? null,
            'type'              => $validated['type'],
            'value'             => $validated['value'],
            'due_date'          => $validated['due_date'],
            'billing_type'      => $validated['billing_type'],
            'description'       => $validated['description'],
            'installment_count' => $validated['installment_count'] ?? null,
            'cycle'             => $validated['cycle'] ?? 'MONTHLY',
        ]);

        if (! $invoice) {
            return response()->json([
                'error' => 'Falha ao criar cobrança no Asaas. Verifique os dados e tente novamente.',
            ], 422);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Cobrança criada com sucesso!',
            'invoice_id'  => $invoice->id,
            'invoice_url' => $invoice->invoice_url,
            'pix_qrcode'  => $invoice->pix_qrcode,
            'status'      => $invoice->status,
        ]);
    }

    /**
     * Cancelar uma cobrança pendente.
     */
    public function cancel(int $id): JsonResponse
    {
        $invoice = TenantInvoice::findOrFail($id);

        if (! $invoice->isPending() && ! $invoice->isOverdue()) {
            return response()->json([
                'error' => 'Apenas cobranças pendentes ou vencidas podem ser canceladas.',
            ], 422);
        }

        if ($invoice->asaas_payment_id) {
            $result = $this->asaasService->cancelPayment($invoice->asaas_payment_id);
            if (! $result) {
                return response()->json([
                    'error' => 'Falha ao cancelar no Asaas. Serviço temporariamente indisponível.',
                ], 503);
            }
        }

        $invoice->update(['status' => 'CANCELED']);

        return response()->json([
            'success' => true,
            'message' => 'Cobrança cancelada com sucesso.',
        ]);
    }

    /**
     * Reenviar notificação (email/sms) via Asaas para o cliente.
     */
    public function resendNotification(int $id): JsonResponse
    {
        $invoice = TenantInvoice::findOrFail($id);

        if (! $invoice->asaas_payment_id) {
            return response()->json(['error' => 'Cobrança sem ID do Asaas.'], 422);
        }

        // O Asaas reenvia notificação via POST /v3/payments/{id}/resendNotification (não documentado oficialmente)
        // Alternativa: consultar o payment e retornar a invoiceUrl para compartilhar manualmente
        $payment = $this->asaasService->getPayment($invoice->asaas_payment_id);

        if (! $payment) {
            return response()->json(['error' => 'Falha ao consultar cobrança no Asaas. Serviço temporariamente indisponível.'], 503);
        }

        return response()->json([
            'success'     => true,
            'invoice_url' => $payment['invoiceUrl'] ?? $invoice->invoice_url,
            'message'     => 'Link de pagamento atualizado.',
        ]);
    }

    /**
     * API: Dados do cliente Asaas por person_id (para modal).
     */
    public function getCustomerByPerson(int $personId): JsonResponse
    {
        $customer = TenantAsaasCustomer::where('person_id', $personId)->first();

        if (! $customer) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists'             => true,
            'asaas_customer_id'  => $customer->asaas_customer_id,
            'name'               => $customer->name,
            'cpf_cnpj'           => $customer->cpf_cnpj,
        ]);
    }
}
