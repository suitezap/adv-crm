<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\ConnectionController;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin\WhatsappImportController;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin\WhatsappTemplatesController;
use SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin\WhatsappChatController;

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

    // Gestão de Instância
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

    // Templates de Mensagens — view customizada com filtros por categoria
    Route::controller(WhatsappTemplatesController::class)->group(function () {
        Route::get('templates', 'index')->name('admin.lawfirm.whatsapp.templates');
        Route::post('templates/save', 'save')->name('admin.lawfirm.whatsapp.templates.save');
    });

    // ── Messenger Inbox (SUSPENSO EM 29/05/2026 - Não fará parte das versões posteriores) ──
    // Route::prefix('messenger')->controller(WhatsappChatController::class)->group(function () {
    //     Route::get('', 'index')->name('admin.lawfirm.whatsapp.messenger');
    //     Route::get('tickets', 'ticketList')->name('admin.lawfirm.whatsapp.messenger.tickets');
    //     Route::get('tickets/{ticketId}/messages', 'messages')->name('admin.lawfirm.whatsapp.messenger.messages');
    //     Route::post('tickets/{ticketId}/accept', 'accept')->name('admin.lawfirm.whatsapp.messenger.accept');
    //     Route::post('tickets/{ticketId}/close', 'close')->name('admin.lawfirm.whatsapp.messenger.close');
    //     Route::post('tickets/{ticketId}/send', 'sendMessage')->name('admin.lawfirm.whatsapp.messenger.send');
    //     Route::post('tickets/{ticketId}/send-media', 'sendMedia')->name('admin.lawfirm.whatsapp.messenger.send_media');
    //     Route::post('upload-media', 'uploadMedia')->name('admin.lawfirm.whatsapp.messenger.upload');
    //     Route::post('start-conversation', 'startConversation')->name('admin.lawfirm.whatsapp.messenger.start');
    //     Route::get('chat-test', 'chatTest')->name('admin.lawfirm.whatsapp.messenger.test');
    // });
});
