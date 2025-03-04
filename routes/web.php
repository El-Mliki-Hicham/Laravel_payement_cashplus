<?php

use App\Http\Controllers\CashPlusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/generate-token', [CashPlusController::class, 'generateToken'])->name('generate.token');

Route::get('/check-token-status', [CashPlusController::class, 'statusToken']);
    Route::post('/handle-callback', [CashPlusController::class, 'handleCallback']);