<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\TenantFinance\Http\Controllers\InvoiceController;
use SuiteZap\LawFirm\TenantFinance\Http\Controllers\TenantAsaasSettingsController;
use SuiteZap\LawFirm\TenantFinance\Http\Middleware\CheckTenantFinanceModule;

/*
|--------------------------------------------------------------------------
| TenantFinance Domain Routes
|--------------------------------------------------------------------------
|
| Cobranças do escritório para clientes finais via Asaas.
| Loaded under prefix '/admin/juridico' in master routes.php.
|
| SKILL.md §6 — Module Gate: Todas as rotas protegidas pelo middleware
| CheckTenantFinanceModule. Retorna 403 se TENANT_FINANCE não está ativo.
|
*/

Route::middleware([CheckTenantFinanceModule::class])->group(function () {

    // ── Configurações Asaas do Escritório ─────────────────

    Route::get('/cobrancas/settings', [TenantAsaasSettingsController::class, 'index'])
        ->name('admin.lawfirm.tenant_finance.settings');

    Route::post('/cobrancas/settings', [TenantAsaasSettingsController::class, 'store'])
        ->name('admin.lawfirm.tenant_finance.settings.store');

    // ── CRUD de Cobranças ─────────────────────────────────

    Route::get('/cobrancas', [InvoiceController::class, 'index'])
        ->name('admin.lawfirm.tenant_finance.index');

    Route::post('/cobrancas/store', [InvoiceController::class, 'store'])
        ->name('admin.lawfirm.tenant_finance.store');

    // ── API AJAX (para modais dentro do Processo) ─────────
    // Declarada ANTES de /{id} para não ser sombreada (show('api') → 404).

    Route::get('/cobrancas/api/customers/{person_id}', [InvoiceController::class, 'getCustomerByPerson'])
        ->name('admin.lawfirm.tenant_finance.api.customer');

    Route::get('/cobrancas/{id}', [InvoiceController::class, 'show'])
        ->whereNumber('id')
        ->name('admin.lawfirm.tenant_finance.show');

    Route::post('/cobrancas/{id}/cancel', [InvoiceController::class, 'cancel'])
        ->whereNumber('id')
        ->name('admin.lawfirm.tenant_finance.cancel');

    Route::post('/cobrancas/{id}/resend', [InvoiceController::class, 'resendNotification'])
        ->whereNumber('id')
        ->name('admin.lawfirm.tenant_finance.resend');
});
