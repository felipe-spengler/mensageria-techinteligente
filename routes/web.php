<?php

use App\Http\Controllers\ManualSendController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/admin/login', [LoginController::class, 'login']);
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', [\App\Http\Controllers\PlanController::class, 'index']);
// B2C / Manual Send (Disabled to focus on SaaS)
// Route::get('/enviar', [ManualSendController::class, 'index']);
// Route::get('/envia_api', [ManualSendController::class, 'showEnviaApi']);
// Route::post('/envia_api/send', [ManualSendController::class, 'postEnviaApi']);
// Route::post('/manual-send', [ManualSendController::class, 'store']);
Route::get('/pix/status/{txid}', [ManualSendController::class, 'checkStatus']);
Route::middleware(['auth'])->group(function () {
    // New Manual Admin Dashboard
    Route::get('/admin', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/api-keys', [\App\Http\Controllers\AdminController::class, 'apiKeys'])->name('admin.api_keys');
    Route::get('/admin/logs', [\App\Http\Controllers\AdminController::class, 'logs'])->name('admin.logs');
    Route::get('/admin/whatsapp', [\App\Http\Controllers\AdminController::class, 'whatsapp'])->name('admin.whatsapp');
    Route::get('/admin/tester', [\App\Http\Controllers\AdminController::class, 'tester'])->name('admin.tester');
    Route::post('/admin/whatsapp/start', [\App\Http\Controllers\AdminController::class, 'startWhatsapp'])->name('admin.whatsapp.start');
    Route::post('/admin/whatsapp/schedule', [\App\Http\Controllers\AdminController::class, 'updateSchedule'])->name('admin.whatsapp.schedule');
    Route::post('/admin/save-asaas', [\App\Http\Controllers\AdminController::class, 'saveAsaas'])->name('admin.asaas.save');

    // Financeiro
    Route::get('/admin/financeiro', [\App\Http\Controllers\AdminController::class, 'financeiro'])->name('admin.financeiro');
    Route::post('/admin/financeiro', [\App\Http\Controllers\AdminController::class, 'saveFinanceiro'])->name('admin.financeiro.save');
    Route::post('/admin/financeiro/test', [\App\Http\Controllers\AdminController::class, 'testAsaas'])->name('admin.financeiro.test');

    // Plans
    Route::get('/admin/plans', [\App\Http\Controllers\AdminController::class, 'plans'])->name('admin.plans');
    Route::post('/admin/plans', [\App\Http\Controllers\AdminController::class, 'storePlan'])->name('admin.plans.store');
    Route::put('/admin/plans/{plan}', [\App\Http\Controllers\AdminController::class, 'updatePlan'])->name('admin.plans.update');
    Route::delete('/admin/plans/{plan}', [\App\Http\Controllers\AdminController::class, 'destroyPlan'])->name('admin.plans.destroy');
    
    // API Keys logic
    Route::post('/admin/api-keys', [\App\Http\Controllers\AdminController::class, 'storeApiKey'])->name('admin.api_keys.store');
    Route::delete('/admin/api-keys/{apiKey}', [\App\Http\Controllers\AdminController::class, 'destroyApiKey'])->name('admin.api_keys.destroy');

    // Bridge Status
    Route::get('/admin/bridge/qrcode', [ManualSendController::class, 'getBridgeQrCode']);
    Route::get('/admin/bridge/status', [ManualSendController::class, 'getBridgeStatus']);

    // Database Manager (Raw)
    Route::get('/admin/db-manager/{table?}', [\App\Http\Controllers\DatabaseManagerController::class, 'index'])->name('admin.db_manager');
    Route::post('/admin/db-manager/{table}/{id?}', [\App\Http\Controllers\DatabaseManagerController::class, 'save'])->name('admin.db_manager.save');
    Route::delete('/admin/db-manager/{table}/{id}', [\App\Http\Controllers\DatabaseManagerController::class, 'delete'])->name('admin.db_manager.delete');
});

Route::get('/documentacao', function() {
    return view('docs');
})->name('docs');

Route::get('/bridge-health', [ManualSendController::class, 'getBridgeHealth']);
Route::get('/precos', function() { return redirect('/'); });
Route::get('/purchase/{id}', [\App\Http\Controllers\PlanController::class, 'purchase']);
Route::post('/purchase', [\App\Http\Controllers\PlanController::class, 'processPurchase']);
