<?php

use App\Http\Controllers\CashPlusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get("/test", function(){
    return response()->json(["message" => "Hello World"]);
});


Route::post('/cashplus/generate-token', [CashPlusController::class, 'generateToken']);
