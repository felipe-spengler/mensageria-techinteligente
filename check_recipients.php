<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKeyIds = \App\Models\ApiKey::where('user_id', 3)->pluck('id');

$results = \App\Models\MessageLog::selectRaw('status, COUNT(DISTINCT `to`) as pessoas_unicas, COUNT(*) as total_msgs')
    ->whereIn('api_key_id', $apiKeyIds)
    ->groupBy('status')
    ->get();

echo "Status       | Pessoas Únicas | Total Msgs\n";
echo str_repeat('-', 45) . "\n";

$totalPessoas = 0;
$totalMsgs    = 0;
foreach ($results as $r) {
    echo str_pad($r->status, 12) . " | " . str_pad($r->pessoas_unicas, 14) . " | " . $r->total_msgs . "\n";
    $totalMsgs += $r->total_msgs;
}

// Total de pessoas únicas no geral (independente do status)
$totalUniquePeople = \App\Models\MessageLog::whereIn('api_key_id', $apiKeyIds)
    ->distinct('to')
    ->count('to');

echo str_repeat('-', 45) . "\n";
echo "TOTAL DE PESSOAS ÚNICAS NA CAMPANHA: $totalUniquePeople\n";
echo "TOTAL DE MENSAGENS (todos status):   $totalMsgs\n";
