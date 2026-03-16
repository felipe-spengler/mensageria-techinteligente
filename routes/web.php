<?php

use App\Http\Controllers\ManualSendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\PlanController::class, 'index']);
Route::get('/enviar', [ManualSendController::class, 'index']);
Route::post('/manual-send', [ManualSendController::class, 'store']);
Route::get('/pix/status/{txid}', [ManualSendController::class, 'checkStatus']);

Route::get('/precos', function() { return redirect('/'); });
Route::get('/purchase/{id}', [\App\Http\Controllers\PlanController::class, 'purchase']);
Route::post('/purchase', [\App\Http\Controllers\PlanController::class, 'processPurchase']);

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/bridge/qrcode', [ManualSendController::class, 'getBridgeQrCode']);
    Route::get('/admin/bridge/status', [ManualSendController::class, 'getBridgeStatus']);
});
