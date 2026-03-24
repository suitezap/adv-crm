<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\SaaS\Models\TenantBillingInfo;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Exception;

class TenantBillingController extends Controller
{
    /**
     * Exibe o card/painel de configuração do Billing via AJAX ou renderiza view completa
     */
    public function index()
    {
        $tenantId = MotherShipService::getTenantId();
        
        $billingInfo = TenantBillingInfo::on('mothership')
            ->where('tenant_id', $tenantId)
            ->first();

        // Opcional: injeta o card na view Minha Assinatura
        return view('lawfirm::subscription.billing-info', compact('billingInfo'));
    }

    /**
     * Salva ou atualiza as informações de faturamento do Tenant
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'cpf_cnpj'       => 'required|string|max:30',
            'email'          => 'required|email|max:255',
            'phone'          => 'nullable|string|max:30',
            'postal_code'    => 'required|string|max:20',
            'address'        => 'required|string|max:255',
            'address_number' => 'required|string|max:50',
            'complement'     => 'nullable|string|max:100',
            'province'       => 'required|string|max:255',
            'city'           => 'required|string|max:255',
            'state'          => 'required|string|size:2',
        ]);

        try {
            $tenantId = MotherShipService::getTenantId();

            if (!$tenantId) {
                throw new Exception('O ID do Tenant não está definido no ambiente atual.');
            }

            // Atualiza ou Cria usando updateOrCreate para facilitar
            TenantBillingInfo::on('mothership')->updateOrCreate(
                ['tenant_id' => $tenantId],
                $request->only([
                    'name', 'email', 'cpf_cnpj', 'phone',
                    'postal_code', 'address', 'address_number',
                    'complement', 'province', 'city', 'state' // state=UF
                ])
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dados do comprador gravados com sucesso no MotherShip!',
                ]);
            }

            return redirect()->back()->with('success', 'Dados atualizados com sucesso!');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao salvar: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Falha ao salvar dados do comprador: ' . $e->getMessage());
        }
    }
}
