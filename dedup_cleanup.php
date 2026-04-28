<?php
/**
 * DEDUPLICATION CLEANUP SCRIPT
 * 
 * Lógica:
 * - Chave única = número de destino (to) + conteúdo da mensagem (message)
 * - Mesma mensagem para destinos DIFERENTES = MANTÉM ambas
 * - Mesma mensagem para o MESMO destino = MANTÉM apenas 1, remove as demais
 * 
 * Ações:
 * 1. Redis: Reconstrói a fila mantendo só a primeira ocorrência de cada (to+msg)
 * 2. Banco: Marca os log_ids excedentes como 'cancelled' (para auditoria)
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$redis  = \Illuminate\Support\Facades\Redis::connection();
$prefix = config('database.redis.options.prefix', '');
$keys   = $redis->keys('wpp_messages:*');

echo "=== LIMPEZA DE DUPLICATAS (Redis + Banco) ===\n";
echo "Chave de deduplicação: DESTINO + CONTEÚDO DA MENSAGEM\n\n";

$totalRemoved         = 0;
$totalKept            = 0;
$totalDbCancelled     = 0;

foreach ($keys as $key) {
    $cleanKey = str_replace($prefix, '', $key);
    $items    = $redis->lrange($cleanKey, 0, -1);

    if (empty($items)) continue;

    echo "Processando fila: $cleanKey (" . count($items) . " itens)...\n";

    $seen            = [];  // fingerprint => true
    $keepItems       = [];  // itens que ficam na fila
    $cancelLogIds    = [];  // log_ids do banco a marcar como 'cancelled'

    foreach ($items as $item) {
        $data = json_decode($item, true);

        $to    = $data['to']      ?? '';
        $msg   = $data['message'] ?? '';
        $logId = $data['log_id']  ?? null;

        // Chave de deduplicação: destino + hash do conteúdo
        $fingerprint = $to . '||' . md5($msg);

        if (!isset($seen[$fingerprint])) {
            // Primeira ocorrência: MANTÉM
            $seen[$fingerprint] = true;
            $keepItems[] = $item;
            $totalKept++;
        } else {
            // Duplicata: REMOVE da fila e cancela no banco
            if ($logId) {
                $cancelLogIds[] = $logId;
            }
            $totalRemoved++;
        }
    }

    // --- Atualiza o Redis ---
    // Apaga a fila atual e reescreve só com os itens únicos
    $redis->del($cleanKey);
    if (!empty($keepItems)) {
        // rpush aceita múltiplos valores
        foreach (array_chunk($keepItems, 100) as $chunk) {
            $redis->rpush($cleanKey, ...$chunk);
        }
    }

    // --- Atualiza o Banco ---
    // Marca duplicatas como 'cancelled' (não apaga, apenas altera status para auditoria)
    if (!empty($cancelLogIds)) {
        $cancelled = \App\Models\MessageLog::whereIn('id', $cancelLogIds)
            ->whereIn('status', ['queued', 'scheduled'])
            ->update([
                'status'        => 'cancelled',
                'error_message' => 'Removida pelo sistema de deduplicação - duplicata na fila.',
                'updated_at'    => now(),
            ]);
        $totalDbCancelled += $cancelled;
    }

    echo "  ✅ Mantidos: " . count($keepItems) . " | Removidos: " . (count($items) - count($keepItems)) . " | DB cancelados: " . count($cancelLogIds) . "\n\n";
}

echo "=== RESUMO FINAL ===\n";
echo "  Mensagens mantidas na fila:    $totalKept\n";
echo "  Duplicatas removidas da fila:  $totalRemoved\n";
echo "  Registros cancelados no banco: $totalDbCancelled\n";
echo "=====================\n";
