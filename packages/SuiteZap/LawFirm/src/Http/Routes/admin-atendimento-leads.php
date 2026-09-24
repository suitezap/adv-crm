<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Atendimento\Http\Controllers\ChatwootLeadController;

/*
|--------------------------------------------------------------------------
| Atendimento Domain — Authenticated Routes
|--------------------------------------------------------------------------
|
| Internal proxy to the Chatwoot API, scoped to a CRM Lead.
| All routes require the standard 'user' middleware (see routes.php group).
|
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
| Endpoints:
|   GET  atendimento/leads/{lead}/chatwoot/messages
|   POST atendimento/leads/{lead}/chatwoot/send
|   GET  atendimento/leads/{lead}/chatwoot/canned-responses
|   GET  atendimento/leads/{lead}/chatwoot/macros
|   POST atendimento/leads/{lead}/chatwoot/macros/{macroId}/run
|
*/

Route::prefix('atendimento/leads/{lead}/chatwoot')
    ->controller(ChatwootLeadController::class)
    ->group(function () {
        Route::get('messages', 'messages')->name('admin.lawfirm.chatwoot.lead.messages');
        Route::post('send', 'send')->name('admin.lawfirm.chatwoot.lead.send');
        Route::get('canned-responses', 'cannedResponses')->name('admin.lawfirm.chatwoot.lead.canned_responses');
        Route::get('macros',           'macros')         ->name('admin.lawfirm.chatwoot.lead.macros');
        Route::post('macros/{macroId}/run', 'runMacro') ->name('admin.lawfirm.chatwoot.lead.macro_run');
        Route::post('messages/{messageId}/transcribe', 'transcribeAudio')->name('admin.lawfirm.chatwoot.lead.transcribe');
        Route::post('stage',           'updateStage')    ->name('admin.lawfirm.chatwoot.lead.stage.update');
    });
