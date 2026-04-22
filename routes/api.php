<?php

use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api-global')->group(function () {
    Route::post('/send', [MessageController::class, 'send'])
        ->middleware([
            \App\Http\Middleware\ValidateApiKey::class,
            'throttle:api-send',
        ]);
    Route::get('/qrcode', [MessageController::class, 'qrcode'])
        ->middleware(\App\Http\Middleware\ValidateApiKey::class);
    Route::get('/logs', [MessageController::class, 'logs'])
        ->middleware(\App\Http\Middleware\ValidateApiKey::class);
    Route::post('/webhook/status', [\App\Http\Controllers\Api\WebhookController::class, 'status']);
    Route::post('/webhook/instance-status', [\App\Http\Controllers\Api\WebhookController::class, 'instanceStatus']);
    Route::post('/webhook/asaas', [\App\Http\Controllers\Api\AsaasWebhookController::class, 'handle']);
});
