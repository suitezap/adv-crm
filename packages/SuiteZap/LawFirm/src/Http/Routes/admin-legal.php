<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Legal\Http\Controllers\ProcessoController;
use SuiteZap\LawFirm\Legal\Http\Controllers\PrazoController;
use SuiteZap\LawFirm\Legal\Http\Controllers\DeadlineController;
use SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController;

/*
|--------------------------------------------------------------------------
| Legal Domain Routes
|--------------------------------------------------------------------------
|
| Processos (Cases), Prazos (Deadlines), Legal Deadlines.
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
*/

// -----------------------------------------------
// Processos (Legal Cases) CRUD
// -----------------------------------------------
Route::prefix('processos')->controller(ProcessoController::class)->group(function () {
    Route::get('', 'index')->name('admin.processos.index');
    Route::get('create', 'create')->name('admin.processos.create');
    Route::post('create', 'store')->name('admin.processos.store');
    Route::get('search-person', 'searchPerson')->name('admin.processos.search_person');
    Route::get('search-lead', 'searchLead')->name('admin.processos.search_lead');
    Route::get('{id}', 'show')->name('admin.processos.show');
    Route::get('{id}/edit', 'edit')->name('admin.processos.edit');
    Route::put('{id}', 'update')->name('admin.processos.update');
    Route::delete('{id}', 'destroy')->name('admin.processos.destroy');
    Route::delete('anexo/{id}', [ProcessDocumentController::class, 'destroy'])->name('admin.processos.delete_attachment');
    Route::get('anexo/download/{id}', [ProcessDocumentController::class, 'downloadAttachment'])->name('admin.processos.download_attachment');
    Route::post('documentos/store', [ProcessDocumentController::class, 'store'])->name('admin.processos.store_documents');
    Route::post('mass-delete', 'massDestroy')->name('admin.processos.mass_delete');

    // Filtered DataGrids (context tabs)
    Route::get('leads/processos/{id}', 'leadProcessos')->name('admin.leads.processos');
    Route::get('contacts/persons/processos/{id}', 'personProcessos')->name('admin.contacts.persons.processos');
    Route::get('contacts/organizations/processos/{id}', 'organizationProcessos')->name('admin.contacts.organizations.processos');
});

// -----------------------------------------------
// Prazos (Deadlines) CRUD
// -----------------------------------------------
Route::prefix('prazos')->controller(PrazoController::class)->group(function () {
    Route::get('', 'index')->name('admin.prazos.index');
    Route::post('store', 'store')->name('admin.prazos.store');
    Route::get('{id}/edit', 'edit')->name('admin.prazos.edit');
    Route::put('{id}', 'update')->name('admin.prazos.update');
    Route::put('{id}/concluir', 'concluir')->name('admin.prazos.concluir');
    Route::get('notify/{id}', 'notifyClient')->name('lawfirm.prazos.notify');
    Route::delete('{id}', 'destroy')->name('admin.prazos.destroy');
});

// -----------------------------------------------
// Legal Deadlines (Domain Refactor)
// -----------------------------------------------
Route::prefix('prazos-legal')->controller(DeadlineController::class)->group(function () {
    Route::post('store', 'store')->name('admin.lawfirm.legal.deadlines.store');
    Route::post('store-new', 'store')->name('admin.lawfirm.legal.prazos.store'); // Alias for user's template
    Route::put('{id}', 'update')->name('admin.lawfirm.legal.deadlines.update');
    Route::delete('{id}', 'destroy')->name('admin.lawfirm.legal.deadlines.destroy');
    Route::delete('delete-new/{id}', 'destroy')->name('admin.lawfirm.legal.prazos.destroy'); // Alias for user's template
    Route::post('{id}/toggle', 'toggle')->name('admin.lawfirm.legal.deadlines.toggle');
});
