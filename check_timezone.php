<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Verifica o fuso horário do app e do banco
echo "APP_TIMEZONE: " . config('app.timezone') . "\n";
echo "Carbon::now(): " . \Carbon\Carbon::now() . "\n";
echo "Carbon::now('UTC'): " . \Carbon\Carbon::now('UTC') . "\n";
echo "now('UTC')->subMinutes(5): " . now('UTC')->subMinutes(5) . "\n\n";

// Pega um registro queued e mostra o updated_at raw do banco
$log = \App\Models\MessageLog::where('status', 'queued')
    ->whereHas('apiKey', fn($q) => $q->where('user_id', 3))
    ->first();

if ($log) {
    echo "Log ID: {$log->id}\n";
    echo "updated_at (Eloquent): " . $log->updated_at . "\n";
    echo "updated_at (raw DB): ";
    $raw = \Illuminate\Support\Facades\DB::select('SELECT updated_at FROM message_logs WHERE id = ?', [$log->id]);
    echo $raw[0]->updated_at . "\n\n";
    
    // Simula a query do agendador
    $threshold = now('UTC')->subMinutes(5);
    echo "Threshold usado na query: " . $threshold . "\n";
    echo "updated_at < threshold? " . ($log->updated_at < $threshold ? "SIM (vai reprocessar!)" : "NÃO (correto)") . "\n";
}
