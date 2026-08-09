<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Escavador\Http\Controllers\WebhookController;

// ============================================================================
// PUBLIC WEBHOOK — /api/webhooks/escavador (sem auth, sem CSRF)
// Isento de CSRF via VerifyCsrfToken::$except no app principal.
// ============================================================================
Route::middleware(['api'])->group(function () {
    Route::post('api/webhooks/escavador', [WebhookController::class, 'handle'])
        ->name('webhooks.escavador');

    Route::post('api/webhooks/asaas', [\SuiteZap\LawFirm\SaaS\Http\Controllers\AsaasWebhookController::class, 'handle'])
        ->name('webhooks.asaas');

    Route::post('api/webhooks/tenant-asaas', [\SuiteZap\LawFirm\TenantFinance\Http\Controllers\TenantAsaasWebhookController::class, 'handle'])
        ->name('webhooks.tenant_asaas');

    // ── Chatwoot Atendimento Webhook (CSRF-exempt — receives Chatwoot callbacks) ──
    Route::post('api/webhooks/chatwoot', [\SuiteZap\LawFirm\Atendimento\Http\Controllers\ChatwootWebhookController::class, 'handle'])
        ->name('webhooks.chatwoot');

    // ── WhatsApp Messenger Inbox Webhook (CSRF-exempt — receives Evolution API callbacks) ──
    Route::post('api/webhooks/whatsapp-messenger/{tenantId}', [\SuiteZap\LawFirm\Whatsapp\Http\Controllers\WhatsappWebhookController::class, 'handle'])
        ->name('webhooks.whatsapp_messenger');
});

// ============================================================================
// PUBLIC WEB ROUTES (Customer Portal)
// ============================================================================
Route::middleware(['web'])->group(function () {
    Route::get('portal/processo/{id}', [\SuiteZap\LawFirm\Legal\Http\Controllers\PublicPortal\CustomerPortalController::class, 'index'])
        ->name('lawfirm.public.portal.index');
    Route::post('portal/processo/{id}/update', [\SuiteZap\LawFirm\Legal\Http\Controllers\PublicPortal\CustomerPortalController::class, 'update'])
        ->name('lawfirm.public.portal.update');
    Route::post('portal/processo/{id}/upload', [\SuiteZap\LawFirm\Legal\Http\Controllers\PublicPortal\CustomerPortalController::class, 'upload'])
        ->name('lawfirm.public.portal.upload');
});

/*
|--------------------------------------------------------------------------
| LawFirm Package — Master Route Loader
|--------------------------------------------------------------------------
|
| Single source of truth for all admin routes.
| Domain files are loaded from Http/Routes/.
|
| Route structure:
|   /admin/juridico/...  → Main legal & functional routes
|   /admin/lawfirm/...   → Legacy compatibility (financial, checklist, dashboard)
|
*/

// ============================================================================
// DEBUG ROUTES — Only available in local environment
// ============================================================================
if (app()->environment('local')) {
    Route::middleware(['web'])
        ->prefix(config('app.admin_path', 'admin').'/lawfirm')
        ->group(function () {
            Route::get('debug-status', function () {
                return response('LawFirm Package is ACTIVE', 200)
                    ->header('Content-Type', 'text/plain');
            })->name('admin.lawfirm.debug_status');
        });
}

// ============================================================================
// GRACEFUL REDIRECTS — Catch malformed URLs without required IDs (405 fixes)
// ============================================================================
Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path', 'admin'))
    ->group(function () {
        // /admin/leads/view (sem ID) → redireciona para lista de leads
        Route::get('leads/view', function () {
            return redirect()->route('admin.leads.index');
        })->name('admin.leads.view.fallback');

        // /admin/leads/edit (sem ID) → redireciona para lista de leads
        Route::get('leads/edit', function () {
            return redirect()->route('admin.leads.index');
        })->name('admin.leads.edit.fallback');
    });

// ============================================================================
// MAIN ROUTES — /admin/juridico
// ============================================================================
Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path', 'admin').'/juridico')
    ->group(function () {
        require __DIR__.'/Routes/admin-legal.php';
        require __DIR__.'/Routes/admin-ged.php';
        require __DIR__.'/Routes/admin-saas.php';
        require __DIR__.'/Routes/admin-whatsapp.php';
        require __DIR__.'/Routes/admin-escavador.php';
        require __DIR__.'/Routes/admin-datajud.php';
        require __DIR__.'/Routes/admin-tenant-finance.php';
    });

// ============================================================================
// LEGACY ROUTES — /admin/lawfirm (backward compatibility)
// ============================================================================
Route::middleware(['web', 'user'])
    ->prefix('admin/lawfirm')
    ->group(function () {

        // Dashboard
        Route::get('/', function () {
            return view('lawfirm::admin.index');
        })->name('admin.lawfirm.index');

        // Financial (loaded from domain file)
        require __DIR__.'/Routes/admin-financial.php';

        // Debug routes — only in local environment
        if (app()->environment('local')) {
            Route::get('debug-view', function () {
                $viewName = 'lawfirm::contacts.persons.edit-extension';

                return response()->json([
                    'view_name' => $viewName,
                    'exists'    => \Illuminate\Support\Facades\View::exists($viewName),
                    'hints'     => (function() { /** @var \Illuminate\View\FileViewFinder $finder */ $finder = app('view')->getFinder(); return $finder->getHints(); })(),
                ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            })->name('admin.lawfirm.debug_view');

            Route::get('debug-permissions', function () {
                $user = auth()->guard('user')->user();
                if (! $user) {
                    return response('Usuário não logado.', 401);
                }

                return [
                    'user_name'   => $user->name,
                    'role_name'   => $user->role->name,
                    'permissions' => $user->role->permissions,
                ];
            })->name('admin.lawfirm.debug_permissions');
        }

        // Checklist Module
        Route::prefix('checklist')->group(function () {
            Route::get('/{leadId}', [\SuiteZap\LawFirm\Legal\Http\Controllers\Admin\ChecklistController::class, 'show'])
                ->name('lawfirm.checklist.show');

            Route::post('/{leadId}/init', [\SuiteZap\LawFirm\Legal\Http\Controllers\Admin\ChecklistController::class, 'initialize'])
                ->name('lawfirm.checklist.init');

            Route::post('/{leadId}/save', [\SuiteZap\LawFirm\Legal\Http\Controllers\Admin\ChecklistController::class, 'saveProgress'])
                ->name('lawfirm.checklist.save');

            Route::post('/{leadId}/validate-ai', [\SuiteZap\LawFirm\Legal\Http\Controllers\Admin\ChecklistController::class, 'validateWithAi'])
                ->name('lawfirm.checklist.validate');

            Route::post('/{leadId}/execute-ai', [\SuiteZap\LawFirm\Legal\Http\Controllers\Admin\ChecklistController::class, 'executeAi'])
                ->name('lawfirm.checklist.execute-ai');
        });
    });
