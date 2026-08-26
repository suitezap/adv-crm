<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Http\Controllers\Admin\Whatsapp\ConnectionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/saas-debug/whatsapp-test', function () {
    $controller = app(ConnectionController::class);
    $response = $controller->status();

    return $response;
});
