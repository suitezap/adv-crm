<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\ConnectionController;

/*
|--------------------------------------------------------------------------
| Whatsapp Domain Routes
|--------------------------------------------------------------------------
|
| WhatsApp Integration (Evolution API) — gestão de instâncias e status.
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
| Movido de admin-saas.php para garantir segregação de domínio.
| Referência arquitetural: ARCHITECTURE.md §3.2
|
*/

Route::prefix('whatsapp')->controller(ConnectionController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.whatsapp.index');
    Route::post('connect', 'connect')->name('admin.lawfirm.whatsapp.connect');
    Route::post('disconnect', 'disconnect')->name('admin.lawfirm.whatsapp.disconnect');
    Route::get('status', 'status')->name('admin.lawfirm.whatsapp.status');
});
