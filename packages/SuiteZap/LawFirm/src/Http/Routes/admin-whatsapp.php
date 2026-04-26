<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\ConnectionController;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin\WhatsappImportController;

/*
|--------------------------------------------------------------------------
| Whatsapp Domain Routes
|--------------------------------------------------------------------------
|
| WhatsApp Integration (Evolution API) — gestão de instâncias e status.
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
*/

Route::prefix('whatsapp')->group(function () {
    // Gestão de Instância — delegado ao ConnectionController (domínio Whatsapp)
    Route::controller(ConnectionController::class)->group(function () {
        Route::get('', 'index')->name('admin.lawfirm.whatsapp.index');
        Route::get('qr-code', 'getQrCode')->name('admin.lawfirm.whatsapp.qr-code');
        Route::get('status', 'getStatus')->name('admin.lawfirm.whatsapp.status');
        Route::post('disconnect', 'disconnect')->name('admin.lawfirm.whatsapp.disconnect');
        Route::post('test', 'testNotification')->name('admin.lawfirm.whatsapp.test');
    });

    // Importação de Mensagens
    Route::controller(WhatsappImportController::class)->group(function () {
        Route::post('importar/{processo_id}', 'dispatchImport')->name('admin.lawfirm.whatsapp.import');
        Route::get('mensagens/{processo_id}', 'fetchMessages')->name('admin.lawfirm.whatsapp.messages');
        Route::get('imports/{processo_id}', 'listImports')->name('admin.lawfirm.whatsapp.imports');
        Route::delete('imports/{processo_id}/{import_id}', 'deleteImport')->name('admin.lawfirm.whatsapp.import.delete');
    });
});
