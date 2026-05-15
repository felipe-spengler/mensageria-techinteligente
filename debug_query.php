<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$messages = \App\Models\MessageLog::where('status', 'queued')
    ->whereHas('apiKey', function($q) { $q->where('user_id', 3); })
    ->limit(5)
    ->get();

echo "Encontradas " . $messages->count() . " mensagens queued para user 3\n";
foreach ($messages as $m) {
    echo "ID: {$m->id}, status: {$m->status}, updated_at: {$m->updated_at}\n";
}

$now = now('America/Sao_Paulo');
$threshold = $now->copy()->subMinutes(15);
echo "Now (SP): $now\n";
echo "Threshold (SP): $threshold\n";

if ($messages->count() > 0) {
    $m = $messages->first();
    echo "ID {$m->id}: updated_at ({$m->updated_at}) < threshold ($threshold)? " . ($m->updated_at < $threshold ? "SIM" : "NÃO") . "\n";
}
