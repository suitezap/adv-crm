<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController;

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
Route::group(['prefix' => 'documentos', 'controller' => ProcessDocumentController::class], function () {
    Route::post('import-v2/{processId}', 'importTemplate')->name('lawfirm.documents.import_v2');
    Route::post('add-item/{processId}', 'addItem')->name('lawfirm.documents.add_item');
    Route::post('send-whatsapp-v2/{processId}', 'sendChecklist')->name('lawfirm.documents.send_whatsapp_v2');
    Route::put('update/{id}', 'updateStatus')->name('lawfirm.documents.update');
    Route::post('mass-update-status', 'massUpdateStatus')->name('lawfirm.documents.mass_update_status');
    Route::post('mass-delete', 'massDestroyChecklist')->name('lawfirm.documents.mass_delete');
    Route::delete('delete/{id}', 'destroyChecklistItem')->name('lawfirm.documents.delete');

});

// -----------------------------------------------
// GED (Gestão Eletrônica de Documentos)
// -----------------------------------------------

Route::prefix('ged')->controller(ProcessDocumentController::class)->group(function () {
    Route::post('store', 'store')->name('admin.lawfirm.ged.store');
    Route::post('upload', 'store')->name('admin.lawfirm.ged.upload'); // Alias for user's template
    Route::delete('{id}', 'destroy')->name('admin.lawfirm.ged.destroy');
    Route::delete('delete/{id}', 'destroy')->name('admin.lawfirm.ged.delete'); // Alias for user's template
    Route::get('download/{id}', 'download')->name('admin.lawfirm.ged.download');
    Route::patch('rename/{id}', 'renameAnexo')->name('admin.lawfirm.ged.rename');
});

// -----------------------------------------------
// Legal Documents (Procuração, Contratos, etc.)
// -----------------------------------------------
Route::get('documentos/procuracao/{processId}', [ProcessDocumentController::class, 'downloadProcuration'])
    ->name('lawfirm.documents.procuration');

Route::get('documentos/contrato/{processId}', [ProcessDocumentController::class, 'downloadContract'])
    ->name('lawfirm.documents.contract');
