<?php

use Illuminate\Support\Facades\Route;

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
        ->prefix(config('app.admin_path', 'admin') . '/lawfirm')
        ->group(function () {
            Route::get('debug-status', function () {
                return response('LawFirm Package is ACTIVE', 200)
                    ->header('Content-Type', 'text/plain');
            })->name('admin.lawfirm.debug_status');
        });
}

// ============================================================================
// MAIN ROUTES — /admin/juridico
// ============================================================================
Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path', 'admin') . '/juridico')
    ->group(function () {
        require __DIR__ . '/Routes/admin-legal.php';
        require __DIR__ . '/Routes/admin-ged.php';
        require __DIR__ . '/Routes/admin-saas.php';
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
        require __DIR__ . '/Routes/admin-financial.php';

        // Debug routes — only in local environment
        if (app()->environment('local')) {
            Route::get('debug-view', function () {
                $viewName = 'lawfirm::contacts.persons.edit-extension';
                return response()->json([
                    'view_name' => $viewName,
                    'exists' => \Illuminate\Support\Facades\View::exists($viewName),
                    'hints' => app('view')->getFinder()->getHints(),
                ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            })->name('admin.lawfirm.debug_view');

            Route::get('debug-permissions', function () {
                $user = auth()->guard('user')->user();
                if (!$user)
                    return response('Usuário não logado.', 401);
                return [
                    'user_name' => $user->name,
                    'role_name' => $user->role->name,
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
