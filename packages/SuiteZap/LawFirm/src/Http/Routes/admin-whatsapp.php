<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Http\Controllers\WhatsappController;

/*
|--------------------------------------------------------------------------
| Whatsapp Domain Routes
|--------------------------------------------------------------------------
|
| WhatsApp Integration (Evolution API) — gestão de instâncias e status.
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
*/

Route::prefix('whatsapp')->controller(WhatsappController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.whatsapp.index');
    Route::get('qr-code', 'getQrCode')->name('admin.lawfirm.whatsapp.qr-code');
    Route::get('status', 'getStatus')->name('admin.lawfirm.whatsapp.status');
    Route::post('disconnect', 'disconnect')->name('admin.lawfirm.whatsapp.disconnect');
    Route::post('test', 'testNotification')->name('admin.lawfirm.whatsapp.test');
});
