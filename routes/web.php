<?php

use App\Http\Controllers\ManualSendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ManualSendController::class, 'index']);
Route::get('/manual-send', [ManualSendController::class, 'index']);
Route::post('/manual-send', [ManualSendController::class, 'store']);
Route::get('/pix/status/{txid}', [ManualSendController::class, 'checkStatus']);

Route::get('/precos', [\App\Http\Controllers\PlanController::class, 'index']);
Route::get('/purchase/{id}', [\App\Http\Controllers\PlanController::class, 'purchase']);

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/bridge/qrcode', [ManualSendController::class, 'getBridgeQrCode']);
    Route::get('/admin/bridge/status', [ManualSendController::class, 'getBridgeStatus']);
});
