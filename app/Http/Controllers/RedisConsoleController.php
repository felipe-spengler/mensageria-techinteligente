<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class RedisConsoleController extends Controller
{
    // Comandos permitidos (somente leitura + operações seguras)
    private array $allowedCommands = [
        'KEYS', 'LLEN', 'LRANGE', 'GET', 'TTL', 'TYPE', 'EXISTS',
        'HGETALL', 'SMEMBERS', 'DBSIZE', 'INFO', 'PING',
    ];

    // Comandos de escrita perigosos - bloqueados
    private array $blockedCommands = [
        'FLUSHALL', 'FLUSHDB', 'DEL', 'SET', 'RPUSH', 'LPUSH',
        'CONFIG', 'SHUTDOWN', 'SAVE', 'BGSAVE', 'REPLICAOF',
    ];

    public function index(Request $request)
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $redis = Redis::connection();
        $prefix = config('database.redis.options.prefix', '');

        // === Visão Geral das Filas ===
        $queues = [];
        $rawKeys = $redis->keys('wpp_messages:*');
        foreach ($rawKeys as $key) {
            $cleanKey = str_replace($prefix, '', $key);
            $session  = str_replace('wpp_messages:', '', $cleanKey);
            $size     = $redis->llen($cleanKey);

            // Pega a próxima mensagem sem remover
            $nextRaw  = $redis->lindex($cleanKey, 0);
            $next     = $nextRaw ? json_decode($nextRaw, true) : null;

            $queues[$session] = [
                'key'      => $cleanKey,
                'size'     => $size,
                'next_to'  => $next['to'] ?? null,
                'next_msg' => isset($next['message']) ? substr($next['message'], 0, 60) : null,
            ];
        }

        // === Chaves de Deduplicação ativas ===
        $dedupKeys = collect($redis->keys('dedup:*'))
            ->map(fn($k) => str_replace($prefix, '', $k))
            ->take(50);

        // === Worker Status ===
        $workerStatus = [];
        $workerKeys = $redis->keys('wpp_worker:*');
        foreach ($workerKeys as $wKey) {
            $cleanWKey = str_replace($prefix, '', $wKey);
            $workerStatus[$cleanWKey] = $redis->get($cleanWKey);
        }

        // === Executar Comando (se vier via POST) ===
        $commandResult = null;
        $commandError  = null;
        $lastCommand   = $request->session()->get('redis_last_command');

        if ($request->isMethod('post') && $request->has('command')) {
            [$commandResult, $commandError] = $this->runCommand($redis, $request->command, $prefix);
            $request->session()->put('redis_last_command', $request->command);
        }

        // === Receitas Pré-definidas ===
        $recipes = $this->getRecipes();

        return view('admin.redis-console', compact(
            'queues', 'dedupKeys', 'workerStatus',
            'commandResult', 'commandError', 'lastCommand', 'recipes'
        ));
    }

    public function run(Request $request)
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $request->validate(['command' => 'required|string|max:500']);

        $redis  = Redis::connection();
        $prefix = config('database.redis.options.prefix', '');

        [$result, $error] = $this->runCommand($redis, $request->command, $prefix);

        return back()
            ->with('redis_result', $result)
            ->with('redis_error', $error)
            ->with('redis_last_command', $request->command);
    }

    private function runCommand($redis, string $rawCommand, string $prefix): array
    {
        try {
            $parts   = preg_split('/\s+/', trim($rawCommand), -1, PREG_SPLIT_NO_EMPTY);
            $command = strtoupper(array_shift($parts));

            if (in_array($command, $this->blockedCommands)) {
                return [null, "⛔ Comando '{$command}' bloqueado por segurança. Use a interface para operações destrutivas."];
            }

            if (!in_array($command, $this->allowedCommands)) {
                return [null, "❌ Comando '{$command}' não permitido. Comandos disponíveis: " . implode(', ', $this->allowedCommands)];
            }

            // Executa o comando via Redis raw
            $result = $redis->command($command, $parts);

            // Formata resultado
            if (is_array($result)) {
                $output = '';
                foreach ($result as $i => $val) {
                    $output .= ($i + 1) . ") " . (is_array($val) ? json_encode($val) : $val) . "\n";
                }
                return [$output ?: '(empty array)', null];
            }

            return [$result === null ? '(nil)' : (string) $result, null];

        } catch (\Exception $e) {
            return [null, $e->getMessage()];
        }
    }

    private function getRecipes(): array
    {
        return [
            [
                'label'   => '📋 Ver todas as filas',
                'command' => 'KEYS wpp_messages:*',
                'color'   => 'blue',
            ],
            [
                'label'   => '📊 Tamanho da fila client_3',
                'command' => 'LLEN wpp_messages:client_3',
                'color'   => 'blue',
            ],
            [
                'label'   => '🔍 Próxima mensagem (client_3)',
                'command' => 'LRANGE wpp_messages:client_3 0 0',
                'color'   => 'amber',
            ],
            [
                'label'   => '📦 5 Primeiras msgs (client_3)',
                'command' => 'LRANGE wpp_messages:client_3 0 4',
                'color'   => 'amber',
            ],
            [
                'label'   => '🔑 Chaves de Deduplicação ativas',
                'command' => 'KEYS dedup:*',
                'color'   => 'purple',
            ],
            [
                'label'   => '⏱️ Worker Status (client_3)',
                'command' => 'GET wpp_worker:next_send:client_3',
                'color'   => 'green',
            ],
            [
                'label'   => '📈 Info do Redis',
                'command' => 'DBSIZE',
                'color'   => 'gray',
            ],
            [
                'label'   => '🏓 Ping',
                'command' => 'PING',
                'color'   => 'green',
            ],
        ];
    }
}
