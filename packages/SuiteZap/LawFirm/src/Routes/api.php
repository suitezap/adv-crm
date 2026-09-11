<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\GED\Http\Controllers\Api\DocumentChecklistApiController;
use SuiteZap\LawFirm\Legal\Http\Controllers\Api\DeadlineApiController;
use SuiteZap\LawFirm\Legal\Http\Controllers\Api\ProcessApiController;
use SuiteZap\LawFirm\SaaS\Http\Controllers\Api\SaasWebhookController;

/*
|--------------------------------------------------------------------------
| LawFirm API Routes
|--------------------------------------------------------------------------
|
| API Routes for LawFirm Package.
| Base Prefix: /api/lawfirm
| Middleware: api, auth:sanctum
|
*/

Route::group(['prefix' => 'api/lawfirm', 'middleware' => ['api', 'auth:sanctum']], function () {

    // ===========================================
    // Processes API
    // ===========================================
    Route::controller(ProcessApiController::class)->prefix('processes')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{id}', 'show');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
    });

    // ===========================================
    // Deadlines API
    // ===========================================
    // ===========================================
    // Deadlines API
    // ===========================================
    Route::controller(DeadlineApiController::class)->prefix('deadlines')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{id}', 'show');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
    });

    // ===========================================
    // Document Checklist API
    // ===========================================
    Route::get('documents/{processId}', [DocumentChecklistApiController::class, 'index']);
    Route::put('documents/{id}', [DocumentChecklistApiController::class, 'update']);
    Route::post('documents/{id}/upload', [DocumentChecklistApiController::class, 'uploadFile']);

});

// ===========================================
// SaaS Webhooks (Secured by Token, not User)
// ===========================================
Route::group(['prefix' => 'api/lawfirm/saas', 'middleware' => ['api']], function () {
    Route::post('webhook', [SaasWebhookController::class, 'updateSubscription']);
});
