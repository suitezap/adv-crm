<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\GED\Http\Controllers\ProcessDocumentController;
use SuiteZap\LawFirm\Legal\Http\Controllers\Admin\DocumentTemplateController;
use SuiteZap\LawFirm\Legal\Http\Controllers\AgendaController;
use SuiteZap\LawFirm\Legal\Http\Controllers\CasoController;
use SuiteZap\LawFirm\Legal\Http\Controllers\DeadlineController;
use SuiteZap\LawFirm\Legal\Http\Controllers\LegalKanbanController;
use SuiteZap\LawFirm\Legal\Http\Controllers\PrazoController;
use SuiteZap\LawFirm\Legal\Http\Controllers\ProcessoController;

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
    Route::get('search-organization', 'searchOrganization')->name('admin.processos.search_organization');
    Route::get('search-lead', 'searchLead')->name('admin.processos.search_lead');
    Route::get('search-caso', [CasoController::class, 'searchCaso'])->name('admin.processos.search_caso');
    Route::get('{id}', 'show')->name('admin.processos.show');
    Route::get('{id}/edit', 'edit')->name('admin.processos.edit');
    Route::put('{id}', 'update')->name('admin.processos.update');
    Route::delete('{id}', 'destroy')->name('admin.processos.destroy');
    Route::delete('anexo/{id}', [ProcessDocumentController::class, 'destroy'])->name('admin.processos.delete_attachment');
    Route::get('anexo/download/{id}', [ProcessDocumentController::class, 'downloadAttachment'])->name('admin.processos.download_attachment');
    Route::post('documentos/store', [ProcessDocumentController::class, 'store'])->name('admin.processos.store_documents');
    Route::post('mass-delete', 'massDestroy')->name('admin.processos.mass_delete');

    // WhatsApp Portal Triggers
    Route::post('{id}/request-registration', 'requestRegistration')->name('admin.processos.request_registration');
    Route::post('{id}/request-documents', 'requestDocuments')->name('admin.processos.request_documents');

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
    Route::get('{id}/toggle-notify', 'toggleNotify')->name('admin.prazos.toggle-notify');
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

// -----------------------------------------------
// Agenda Jurídica (FullCalendar Vanilla JS)
// -----------------------------------------------
Route::prefix('agenda')->controller(AgendaController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.agenda.index');
    Route::get('eventos', 'getEventos')->name('admin.lawfirm.agenda.events');
    Route::post('eventos/atualizar/{id}', 'updateDragDrop')->name('admin.lawfirm.agenda.update');
    Route::post('atividades', 'storeActivity')->name('admin.lawfirm.agenda.store');
});

// -----------------------------------------------
// Kanban Jurídico (Legal Pipeline Board)
// -----------------------------------------------
Route::prefix('kanban')->controller(LegalKanbanController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.legal.kanban.index');
    Route::put('{id}/stage', 'updateStage')->name('admin.lawfirm.legal.kanban.update');
});

// -----------------------------------------------
// Casos (Legal Cases — Parent of Processos) CRUD
// -----------------------------------------------
Route::prefix('casos')->controller(CasoController::class)->group(function () {
    Route::get('', 'index')->name('admin.lawfirm.casos.index');
    Route::get('create', 'create')->name('admin.lawfirm.casos.create');
    Route::post('create', 'store')->name('admin.lawfirm.casos.store');
    Route::get('search', 'searchCaso')->name('admin.lawfirm.casos.search');
    Route::get('search-processo', 'searchProcesso')->name('admin.lawfirm.casos.search_processo');
    Route::post('{id}/link-processo', 'linkProcesso')->name('admin.lawfirm.casos.link_processo');
    Route::post('{id}/unlink-processo/{processoId}', 'unlinkProcesso')->name('admin.lawfirm.casos.unlink_processo');
    Route::get('{id}', 'show')->name('admin.lawfirm.casos.show');
    Route::get('{id}/edit', 'edit')->name('admin.lawfirm.casos.edit');
    Route::put('{id}', 'update')->name('admin.lawfirm.casos.update');
    Route::delete('{id}', 'destroy')->name('admin.lawfirm.casos.destroy');
    Route::post('mass-delete', 'massDestroy')->name('admin.lawfirm.casos.mass_delete');
});

// -----------------------------------------------
// Modelos de Documentos CRUD
// -----------------------------------------------
Route::prefix('modelos-documentos')->controller(DocumentTemplateController::class)->group(function () {
    Route::get('', 'manage')->name('admin.modelos.index');
    Route::get('create', 'create')->name('admin.modelos.create');
    Route::post('', 'store')->name('admin.modelos.store');
    Route::get('{id}/edit', 'edit')->name('admin.modelos.edit');
    Route::put('{id}', 'update')->name('admin.modelos.update');
    Route::delete('{id}', 'destroy')->name('admin.modelos.destroy');

    // Layout templates (Cabeçalho / Rodapé)
    Route::get('layout', 'getLayoutTemplates')->name('admin.modelos.layout.get');
    Route::post('layout/{tipo}', 'saveLayout')->name('admin.modelos.layout.save');
});

Route::prefix('processos')->controller(DocumentTemplateController::class)->group(function () {
    Route::get('{processoId}/modelos', 'index')->name('admin.processos.modelos.index');
    Route::get('{processoId}/modelos/{templateId}/render', 'render')->name('admin.processos.modelos.render');

    // Save rendered document to S3 Drive
    Route::post('{processoId}/modelos/salvar', 'saveGenerated')->name('admin.processos.modelos.salvar');
});
