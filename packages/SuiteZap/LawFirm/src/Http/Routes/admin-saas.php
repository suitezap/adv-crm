<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\SaaS\Http\Controllers\SaaSController;
use SuiteZap\LawFirm\Http\Controllers\ChecklistController;

/*
|--------------------------------------------------------------------------
| SaaS, WhatsApp, Assistants & Checklist Routes
|--------------------------------------------------------------------------
|
| SaaS Dashboard, WhatsApp Integration, AI Assistants, Checklist Module.
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
*/

// -----------------------------------------------
// WhatsApp Integration (Evolution API)
// -----------------------------------------------
Route::prefix('whatsapp')->controller(\SuiteZap\LawFirm\Whatsapp\Http\Controllers\ConnectionController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.whatsapp.index');
    Route::post('connect', 'connect')->name('admin.lawfirm.whatsapp.connect');
    Route::post('disconnect', 'disconnect')->name('admin.lawfirm.whatsapp.disconnect');
    Route::get('status', 'status')->name('admin.lawfirm.whatsapp.status');
});

// -----------------------------------------------
// SaaS Dashboard (Minha Assinatura)
// -----------------------------------------------
Route::get('assinatura', [SaaSController::class, 'index'])
    ->name('admin.lawfirm.saas.index');

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
// AI Assistants
// -----------------------------------------------

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
