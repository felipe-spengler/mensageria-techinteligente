<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$redis = \Illuminate\Support\Facades\Redis::connection();
$prefix = config('database.redis.options.prefix', '');
$keys = $redis->keys('wpp_messages:*');

echo "--- RELATÓRIO: DUPLICATAS NÃO ENVIADAS (REDIS x BANCO) ---\n";
echo "Filas encontradas: " . count($keys) . "\n\n";

$totalDuplicateMessages  = 0;
$totalAffectedRecipients = 0;

foreach ($keys as $key) {
    $cleanKey = str_replace($prefix, '', $key);
    $items = $redis->lrange($cleanKey, 0, -1);

    if (empty($items)) continue;

    // Agrupa por destinatário + conteúdo da mensagem
    $groups = [];
    foreach ($items as $item) {
        $data   = json_decode($item, true);
        $logId  = $data['log_id'] ?? null;
        $to     = $data['to']     ?? 'unknown';
        $msg    = $data['message'] ?? '';

        // Filtra: só inclui se o log_id existe no banco com status pendente
        if ($logId) {
            $log = \App\Models\MessageLog::where('id', $logId)
                ->whereIn('status', ['queued', 'scheduled'])
                ->first();

            if (!$log) continue; // já foi enviada, falhou, ou não existe
        }

        $fingerprint = $to . '||' . md5($msg);
        if (!isset($groups[$fingerprint])) {
            $groups[$fingerprint] = [
                'to'      => $to,
                'preview' => substr($msg, 0, 60),
                'count'   => 0,
                'log_ids' => [],
            ];
        }
        $groups[$fingerprint]['count']++;
        if ($logId) $groups[$fingerprint]['log_ids'][] = $logId;
    }

    // Mostra só os que aparecem mais de 1 vez (duplicatas reais)
    $duplicates = array_filter($groups, fn($g) => $g['count'] > 1);

    if (empty($duplicates)) {
        echo "[$cleanKey] ✅ Nenhuma duplicata pendente.\n\n";
        continue;
    }

    echo "[$cleanKey] ⚠️ " . count($duplicates) . " destinatários com mensagens duplicadas pendentes:\n";
    echo str_repeat('-', 80) . "\n";

    foreach ($duplicates as $dup) {
        echo "  📱 Para: {$dup['to']}\n";
        echo "  📝 Msg:  \"{$dup['preview']}...\"\n";
        echo "  🔁 Na fila: {$dup['count']}x (Log IDs: " . implode(', ', array_unique(array_slice($dup['log_ids'], 0, 5))) . "...)\n\n";
        $totalDuplicateMessages  += $dup['count'];
        $totalAffectedRecipients++;
    }
}

echo str_repeat('=', 80) . "\n";
echo "TOTAIS:\n";
echo "  Destinatários afetados:       $totalAffectedRecipients\n";
echo "  Total de entradas duplicadas: $totalDuplicateMessages\n";
echo str_repeat('=', 80) . "\n";
