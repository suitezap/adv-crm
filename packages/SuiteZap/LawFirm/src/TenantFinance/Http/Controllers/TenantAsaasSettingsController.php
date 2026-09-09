<?php

namespace SuiteZap\LawFirm\TenantFinance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasSetting;

/**
 * TenantAsaasSettingsController
 *
 * Gerencia as credenciais Asaas do escritório.
 * Skinny Controller — apenas orquestra request → model → response.
 */
class TenantAsaasSettingsController extends Controller
{
    public function index()
    {
        abort_if(! bouncer()->hasPermission('lawfirm.financeiro.cobrancas.settings'), 401, 'This action is unauthorized');

        $settings = TenantAsaasSetting::first();

        return view('lawfirm::TenantFinance.settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        abort_if(! bouncer()->hasPermission('lawfirm.financeiro.cobrancas.settings'), 401, 'This action is unauthorized');

        $validated = $request->validate([
            'api_key'       => 'nullable|string|max:255',
            'wallet_id'     => 'nullable|string|max:100',
            'environment'   => 'required|in:sandbox,production',
            'webhook_token' => 'nullable|string|max:255',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = true; // Força ativação caso o formulário seja preenchido

        $settings = TenantAsaasSetting::first();

        if ($settings) {
            // Credenciais nunca trafegam de volta à view: em branco mantém a atual.
            if (empty($validated['api_key'])) {
                unset($validated['api_key']);
            }
            if (empty($validated['webhook_token'])) {
                unset($validated['webhook_token']);
            }
            $settings->update($validated);
        } else {
            if (empty($validated['api_key'])) {
                return back()->withErrors(['api_key' => 'Informe a API Key do Asaas.'])->withInput();
            }
            $settings = TenantAsaasSetting::create($validated);
        }

        session()->flash('success', 'Configurações do Asaas salvas com sucesso.');

        return redirect()->route('admin.lawfirm.tenant_finance.settings');
    }
}
