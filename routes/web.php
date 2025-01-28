<?php

use App\Http\Controllers\CashPlusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/generate-token', [CashPlusController::class, 'generateToken'])->name('generate.token');

Route::post('/check-token-status', [CashPlusController::class, 'statusToken']);
