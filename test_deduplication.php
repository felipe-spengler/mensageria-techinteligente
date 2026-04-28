<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$session = 'client_1';
$to = '5545999940018'; // Real formatted number
$message = 'Teste de Deduplicacao';

$instance = \App\Models\WhatsappInstance::where('session_name', $session)->first();
$apiKey = \App\Models\ApiKey::where('user_id', $instance->user_id)->first();

echo "Tentativa 1...\n";
$response1 = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey->key])
    ->post('http://localhost/api/v1/messages/send', [
        'to' => $to,
        'message' => $message
    ]);
echo "Status 1: " . $response1->status() . " | " . $response1->body() . "\n\n";

echo "Tentativa 2 (Duplicada)...\n";
$response2 = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey->key])
    ->post('http://localhost/api/v1/messages/send', [
        'to' => $to,
        'message' => $message
    ]);
echo "Status 2: " . $response2->status() . " | " . $response2->body() . "\n\n";

echo "Tentativa 3 (Com Force)...\n";
$response3 = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey->key])
    ->post('http://localhost/api/v1/messages/send', [
        'to' => $to,
        'message' => $message,
        'force' => true
    ]);
echo "Status 3: " . $response3->status() . " | " . $response3->body() . "\n";
