<?php

use App\Http\Controllers\ManualSendController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', [\App\Http\Controllers\PlanController::class, 'index']);
Route::get('/enviar', [ManualSendController::class, 'index']);
Route::get('/envia_api', [ManualSendController::class, 'showEnviaApi']);
Route::post('/envia_api/send', [ManualSendController::class, 'postEnviaApi']);
Route::post('/manual-send', [ManualSendController::class, 'store']);
Route::get('/pix/status/{txid}', [ManualSendController::class, 'checkStatus']);
Route::middleware(['auth'])->group(function () {
    // New Manual Admin Dashboard
    Route::get('/admin', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/api-keys', [\App\Http\Controllers\AdminController::class, 'apiKeys'])->name('admin.api_keys');
    Route::get('/admin/logs', [\App\Http\Controllers\AdminController::class, 'logs'])->name('admin.logs');
    Route::get('/admin/whatsapp', [\App\Http\Controllers\AdminController::class, 'whatsapp'])->name('admin.whatsapp');
    Route::post('/admin/save-asaas', [\App\Http\Controllers\AdminController::class, 'saveAsaas'])->name('admin.asaas.save');

    // Bridge Status
    Route::get('/admin/bridge/qrcode', [ManualSendController::class, 'getBridgeQrCode']);
    Route::get('/admin/bridge/status', [ManualSendController::class, 'getBridgeStatus']);
});

Route::get('/bridge-health', [ManualSendController::class, 'getBridgeHealth']);
Route::get('/precos', function() { return redirect('/'); });
Route::get('/purchase/{id}', [\App\Http\Controllers\PlanController::class, 'purchase']);
Route::post('/purchase', [\App\Http\Controllers\PlanController::class, 'processPurchase']);
