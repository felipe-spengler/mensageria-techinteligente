<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Restaura os registros cancelados pelo script de deduplicação de volta para 'queued'
$restored = \App\Models\MessageLog::where('status', 'cancelled')
    ->where('error_message', 'like', '%deduplicação%')
    ->whereHas('apiKey', fn($q) => $q->where('user_id', 3))
    ->update([
        'status'        => 'queued',
        'error_message' => null,
        'updated_at'    => now(),
    ]);

echo "Restaurados: $restored registros de volta para 'queued'\n\n";

// Confirmação dos totais agora
$counts = \App\Models\MessageLog::selectRaw('status, COUNT(*) as total')
    ->whereHas('apiKey', fn($q) => $q->where('user_id', 3))
    ->groupBy('status')
    ->pluck('total', 'status');

echo "Totais no banco (client_3):\n";
foreach ($counts as $status => $total) {
    echo "  $status: $total\n";
}

// Confirma Redis
$redis = \Illuminate\Support\Facades\Redis::connection();
$queueSize = $redis->llen('wpp_messages:client_3');
echo "\nRedis wpp_messages:client_3: $queueSize itens na fila\n";
