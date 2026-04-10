<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\SaaS\Http\Controllers\SaaSController;
use SuiteZap\LawFirm\Http\Controllers\ChecklistController;
use SuiteZap\LawFirm\SaaS\Http\Controllers\Admin\MothershipTemplateController;

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
});

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
// -----------------------------------------------
Route::get('debug/test-s3', function () {
    try {
        // 1. Tenta pegar o disco padrão
        $diskName = config('filesystems.default');
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = \Illuminate\Support\Facades\Storage::disk($diskName);

        // 2. Tenta escrever um arquivo de teste
        $filename = 'test-connection-' . time() . '.txt';
        $content = 'Conexão com S3/MinIO funcionando! ' . now();

        $disk->put($filename, $content);

        // 3. Tenta recuperar a URL
        $url = $disk->url($filename);
        $exists = $disk->exists($filename);

        return response()->json([
            'status' => 'sucesso',
            'disk_config' => $diskName,
            'bucket' => config("filesystems.disks.{$diskName}.bucket") ?? 'N/A',
            'endpoint' => config("filesystems.disks.{$diskName}.endpoint") ?? 'N/A',
            'file_created' => $exists,
            'url_generated' => $url,
            'message' => 'Se você vê isso, o S3 está configurado corretamente.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'erro',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('admin.lawfirm.debug.s3');
