<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\SaaS\Http\Controllers\Admin\MothershipTemplateController;
use SuiteZap\LawFirm\SaaS\Http\Controllers\SaaSController;
use SuiteZap\LawFirm\Atendimento\Http\Middleware\CheckChatwootModule;

/*
|--------------------------------------------------------------------------
| SaaS, Assistants & Checkout Routes
|--------------------------------------------------------------------------
|
| SaaS Dashboard, AI Assistants, Checkout, Billing & Mothership Admin.
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
| WhatsApp routes moved to admin-whatsapp.php (domain segregation — v3.17)
|
*/

// -----------------------------------------------
// SaaS Dashboard (Minha Assinatura)
// -----------------------------------------------
Route::get('assinatura', [SaaSController::class, 'index'])
    ->name('admin.lawfirm.saas.index');

// -----------------------------------------------
// SaaS Checkout & Billing (Asaas)
// -----------------------------------------------
Route::prefix('checkout')->controller(\SuiteZap\LawFirm\SaaS\Http\Controllers\SubscriptionCheckoutController::class)->group(function () {
    Route::post('plan', 'checkoutPlan')->name('admin.lawfirm.saas.checkout.plan');
    Route::post('credits', 'checkoutCredits')->name('admin.lawfirm.saas.checkout.credits');
});

Route::prefix('billing-info')->controller(\SuiteZap\LawFirm\SaaS\Http\Controllers\TenantBillingController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.saas.billing-info.index');
    Route::post('', 'store')->name('admin.lawfirm.saas.billing-info.store');
});

// -----------------------------------------------
// SaaS Orders (Pedidos / Intenções de Compra) — v3.21
// -----------------------------------------------
Route::get('orders', [\SuiteZap\LawFirm\SaaS\Http\Controllers\SaasOrderController::class, 'index'])
    ->name('admin.lawfirm.saas.orders.index');

// -----------------------------------------------
// AI Assistants
// -----------------------------------------------
Route::prefix('assistants')->controller(\SuiteZap\LawFirm\AI\Http\Controllers\Admin\AssistantController::class)->group(function () {
    Route::get('', 'index')->name('lawfirm.assistants.index');
    Route::get('escavai', 'escavai')->name('lawfirm.assistants.escavai');

    // History Routes (must come before {slug})
    Route::prefix('history')->controller(\SuiteZap\LawFirm\AI\Http\Controllers\Admin\AssistantHistoryController::class)->group(function () {
        Route::get('', 'index')->name('lawfirm.assistants.history.index');
        Route::get('{id}', 'show')->name('lawfirm.assistants.history.show');
    });

    Route::post('processar', 'process')->name('lawfirm.assistants.process');
    Route::get('{slug}', 'show')->name('lawfirm.assistants.show');
    Route::post('{slug}/generate', 'generate')->name('lawfirm.assistants.generate');
    Route::post('{slug}/execute', 'execute')->name('lawfirm.assistants.execute');
    Route::get('status/{id}', 'checkStatus')->name('lawfirm.assistants.check_status');

    // Lead Integration
    Route::post('lead/{leadId}/pre-triagem', 'processForLead')->name('lawfirm.assistants.lead.pre-triagem');

    // Cross-Assistant Context (v3.50+)
    Route::get('lead/{leadId}/triagem', 'getTriagem')->name('lawfirm.assistants.triagem.get');
    Route::post('lead/{leadId}/triagem/save', 'saveTriagem')->name('lawfirm.assistants.triagem.save');
});

// -----------------------------------------------
// SAC (Central de Atendimento)
// -----------------------------------------------
Route::get('sac', [\SuiteZap\LawFirm\AI\Http\Controllers\Admin\AssistantController::class, 'chatwoot'])
    ->middleware([CheckChatwootModule::class])
    ->name('lawfirm.assistants.chatwoot');

// -----------------------------------------------
// Mothership Admin API — Gestão de Templates de IA + Cache Sync
//
// Autenticação: Header X-Mothership-Key (lido de mothership.app_config.api_secret)
// Zero .env: o segredo é compartilhado via banco Mothership, não por variável de ambiente.
//
// Fluxo de sync:
//   Mothership Panel salva template → POST /mothership/cache/invalidate →
//   ai_templates_cache_version++ → próximo request do tenant reconstrói cache
// -----------------------------------------------
Route::prefix('mothership')->controller(MothershipTemplateController::class)->group(function () {
    // Templates CRUD (chamados diretamente ou pelo Mothership Panel)
    Route::get('templates', 'index')->name('lawfirm.mothership.templates.index');
    Route::post('templates/upsert', 'upsert')->name('lawfirm.mothership.templates.upsert');
    Route::patch('templates/{slug}/deactivate', 'deactivate')->name('lawfirm.mothership.templates.deactivate');

    // Webhook de invalidação de cache — chamado pelo Mothership Panel após qualquer mutação
    Route::post('cache/invalidate', 'invalidateCache')->name('lawfirm.mothership.cache.invalidate');
});

// -----------------------------------------------
// Diagnóstico S3/MinIO
// Protegido: guard APP_DEBUG=true aplicado no controller.
// CC fix (v3.49): Storage:: direto removido — usa SaasFileService::testConnection().
// -----------------------------------------------
Route::get('debug/test-s3', [SaaSController::class, 'testS3Connection'])
    ->name('admin.lawfirm.debug.s3');
