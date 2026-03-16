<?php

use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/send', [MessageController::class, 'send'])->middleware(\App\Http\Middleware\ValidateApiKey::class);
    Route::post('/webhook/status', [\App\Http\Controllers\Api\WebhookController::class, 'status']);
    Route::post('/webhook/asaas', [\App\Http\Controllers\Api\AsaasWebhookController::class, 'handle']);
});
