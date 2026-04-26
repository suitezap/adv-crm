<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Escavador\Http\Controllers\Admin\EscavadorController;
use SuiteZap\LawFirm\Escavador\Http\Controllers\Admin\EscavadorHistoryController;
use SuiteZap\LawFirm\Escavador\Http\Controllers\Admin\EscavadorMonitoramentoController;
use SuiteZap\LawFirm\SaaS\Http\Controllers\Admin\SaasTransactionController;

/*
|--------------------------------------------------------------------------
| Escavador API Integration Routes
|--------------------------------------------------------------------------
|
| Integração com a API do Escavador (V1 & V2).
| Parent group: prefix 'admin/juridico', middleware ['web', 'admin_locale', 'user']
|
*/

Route::prefix('escavador')->controller(EscavadorController::class)->group(function () {

    // Dashboard
    Route::get('', 'index')->name('lawfirm.escavador.index');

    // Saldo da API Escavador (AJAX)
    Route::get('saldo', 'saldo')->name('lawfirm.escavador.saldo');

    // Saldo do cliente (Subscription) — para modal de confirmação
    Route::get('saldo-cliente', 'saldoCliente')->name('lawfirm.escavador.saldo_cliente');

    // Consulta de processos (V2)
    Route::post('processo', 'consultarProcesso')->name('lawfirm.escavador.processo');
    Route::post('movimentacoes', 'consultarMovimentacoes')->name('lawfirm.escavador.movimentacoes');
    Route::post('envolvido', 'consultarEnvolvido')->name('lawfirm.escavador.envolvido');
    Route::post('oab', 'consultarOab')->name('lawfirm.escavador.oab');

    // Resumo Inteligente IA (V2)
    Route::post('resumo-ia', 'resumoIa')->name('lawfirm.escavador.resumo_ia');

    // Busca genérica por termo (V1)
    Route::post('busca', 'buscarTermo')->name('lawfirm.escavador.busca');

    // --- Processo Escavador (Cache local) ---
    Route::post('sync-processo', 'syncProcesso')->name('lawfirm.escavador.sync_processo');
    Route::get('processo-details/{processoId}', 'getProcessoDetails')->name('lawfirm.escavador.processo_details');
    Route::post('atualizar-tribunal', 'requestAtualizacao')->name('lawfirm.escavador.atualizar_tribunal');
    Route::post('download-autos', 'downloadAutos')->name('lawfirm.escavador.download_autos');

    // Monitoramentos (V1)
    Route::get('monitoramentos', 'monitoramentos')->name('lawfirm.escavador.monitoramentos');

    // Documentos públicos (V2)
    Route::post('documentos', 'documentosPublicos')->name('lawfirm.escavador.documentos');

    // SERVIÇOS PAGOS — Débito de saldo + rastreamento
    Route::post('servico', 'executarServico')->name('lawfirm.escavador.servico');

    // Certificados Digitais (V2) — sem débito de créditos SaaS
    Route::get   ('certificados/config', 'viewCertificados')  ->name('lawfirm.escavador.certificados.view');
    Route::get   ('certificados',      'listarCertificados')  ->name('lawfirm.escavador.certificados.index');
    Route::post  ('certificados',      'cadastrarCertificado')->name('lawfirm.escavador.certificados.store');
    Route::get   ('certificados/{id}', 'retornarCertificado') ->name('lawfirm.escavador.certificados.show');
    Route::delete('certificados/{id}', 'removerCertificado')  ->name('lawfirm.escavador.certificados.destroy');
});
// Histórico dos Assistentes Jurídicos
Route::get('escavador/historico', [EscavadorHistoryController::class, 'index'])
    ->name('lawfirm.escavador.history');

Route::get('escavador/historico/{id}', [EscavadorHistoryController::class, 'show'])
    ->name('lawfirm.escavador.history.show');

// Monitoramentos
Route::get('escavador/monitoramentos', [EscavadorMonitoramentoController::class, 'index'])
    ->name('lawfirm.escavador.monitoramentos.index');

Route::get('escavador/monitoramentos/create', [EscavadorMonitoramentoController::class, 'create'])
    ->name('lawfirm.escavador.monitoramentos.create');

Route::post('escavador/monitoramentos/{id}/toggle-whatsapp', [EscavadorMonitoramentoController::class, 'toggleWhatsapp'])
    ->name('lawfirm.escavador.monitoramentos.toggle_whatsapp');

// Extrato de Consumo de Créditos SaaS e Adições
Route::get('saas/transacoes', [SaasTransactionController::class, 'index'])
    ->name('lawfirm.saas.transactions');

Route::get('saas/adicoes', [SaasTransactionController::class, 'additions'])
    ->name('lawfirm.saas.additions');
