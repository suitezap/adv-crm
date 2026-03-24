<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Financial\Http\Controllers\FinancialController;

/*
|--------------------------------------------------------------------------
| Financial Domain Routes
|--------------------------------------------------------------------------
|
| Financial Dashboard, Quick Pay, PDF Receipt.
| These routes are loaded under the LEGACY prefix '/admin/lawfirm'
| because they were originally defined there and existing links depend on it.
|
*/

// Note: These routes are loaded within the legacy '/admin/lawfirm' group
// in the master routes.php file, NOT under '/admin/juridico'.

Route::get('/financial', [FinancialController::class, 'index'])
    ->name('admin.lawfirm.financial.index');

Route::post('/financial/quick-pay/{id}', [FinancialController::class, 'quickPay'])
    ->name('admin.lawfirm.financial.quick_pay');

Route::get('/financial/receipt/{id}', [FinancialController::class, 'downloadReceipt'])
    ->name('admin.lawfirm.financial.receipt');

Route::post('/financial/process/{id}/store', [FinancialController::class, 'storeProcessFinancials'])
    ->name('admin.lawfirm.financial.process.store');

Route::post('/financial/send-whatsapp/{id}', [FinancialController::class, 'sendWhatsappBilling'])
    ->name('admin.lawfirm.financial.send_whatsapp');
