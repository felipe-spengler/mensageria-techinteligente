<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$logs = \App\Models\MessageLog::where('status', 'queued')
    ->whereHas('apiKey', function($q) { $q->where('user_id', 3); })
    ->limit(1)
    ->get();

foreach ($logs as $log) {
    echo "Log ID: {$log->id}\n";
    echo "API Key ID: {$log->api_key_id}\n";
    $apiKey = $log->apiKey;
    echo "API Key User ID: " . ($apiKey ? $apiKey->user_id : 'NULL') . "\n";
    
    $instance = \App\Models\WhatsappInstance::where('user_id', $apiKey->user_id)->first();
    echo "Instância para User ID {$apiKey->user_id}: " . ($instance ? $instance->session_name : 'NÃO ENCONTRADA') . "\n";
}
