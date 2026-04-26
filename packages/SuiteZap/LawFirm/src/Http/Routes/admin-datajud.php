<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\DataJud\Http\Controllers\DataJudController;

Route::prefix('datajud')->group(function () {
    Route::post('consulta', [DataJudController::class, 'consulta'])->name('lawfirm.datajud.servico');
});
