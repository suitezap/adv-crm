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
        $settings = TenantAsaasSetting::first();

        return view('lawfirm::TenantFinance.settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'api_key'       => 'required|string|max:255',
            'wallet_id'     => 'nullable|string|max:100',
            'environment'   => 'required|in:sandbox,production',
            'webhook_token' => 'nullable|string|max:255',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = true; // Força ativação caso o formulário seja preenchido

        $settings = TenantAsaasSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = TenantAsaasSetting::create($validated);
        }

        session()->flash('success', 'Configurações do Asaas salvas com sucesso.');

        return redirect()->route('admin.lawfirm.tenant_finance.settings');
    }
}
