<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| GED & Document Domain Routes
|--------------------------------------------------------------------------
|
| Document Checklist, GED (store/destroy), Legal Documents (Procuração/Contrato).
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
*/

// -----------------------------------------------
// Document Checklist Routes
// -----------------------------------------------
Route::group(['prefix' => 'documentos', 'controller' => \SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController::class], function () {
    Route::post('import-v2/{processId}', 'importTemplate')->name('lawfirm.documents.import_v2');
    Route::post('send-whatsapp-v2/{processId}', 'sendChecklist')->name('lawfirm.documents.send_whatsapp_v2');
    Route::put('update/{id}', 'updateStatus')->name('lawfirm.documents.update');
    Route::delete('delete/{id}', 'destroyChecklistItem')->name('lawfirm.documents.delete');
});

// -----------------------------------------------
// GED (Gestão Eletrônica de Documentos)
// -----------------------------------------------

Route::prefix('ged')->controller(\SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController::class)->group(function () {
    Route::post('store', 'store')->name('admin.lawfirm.ged.store');
    Route::post('upload', 'store')->name('admin.lawfirm.ged.upload'); // Alias for user's template
    Route::delete('{id}', 'destroy')->name('admin.lawfirm.ged.destroy');
    Route::delete('delete/{id}', 'destroy')->name('admin.lawfirm.ged.delete'); // Alias for user's template
    Route::get('download/{id}', 'download')->name('admin.lawfirm.ged.download'); // New route for download if it exists in controller, otherwise defaults to download logic
});

// -----------------------------------------------
// Legal Documents (Procuração, Contratos, etc.)
// -----------------------------------------------
Route::get('documentos/procuracao/{processId}', [\SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController::class, 'downloadProcuration'])
    ->name('lawfirm.documents.procuration');

Route::get('documentos/contrato/{processId}', [\SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController::class, 'downloadContract'])
    ->name('lawfirm.documents.contract');
