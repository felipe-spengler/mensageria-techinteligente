<?php

namespace App\Console\Commands;

use App\Models\MessageLog;
use App\Models\WhatsappInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RecoverMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recover-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifica mensagens que ficaram travadas no banco com status queued por mais de 10 minutos e as reenviada para o redis.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stuckMessages = MessageLog::where('status', 'queued')
            ->where('created_at', '<', now()->subMinutes(10))
            ->get();

        if ($stuckMessages->isEmpty()) {
            return;
        }

        $this->info("Encontradas {$stuckMessages->count()} mensagens travadas. Iniciando recuperação...");

        foreach ($stuckMessages as $log) {
            try {
                $session = 'mensageria-tech';
                
                // Tenta encontrar a instância do usuário para saber qual fila usar
                $instance = WhatsappInstance::where('user_id', $log->apiKey->user_id)->first();
                
                if ($instance) {
                    $session = $instance->session_name;
                }

                $this->line("Re-enviando ID {$log->id} para a fila: {$session}");

                Redis::rpush('wpp_messages:' . $session, json_encode([
                    'log_id' => $log->id,
                    'to' => $log->to,
                    'message' => $log->message,
                    'media' => $log->media_url,
                    'session' => $session
                ]));

                // Logamos no Laravel também para fins de auditoria
                Log::notice("Mensagem de recuperação enviada para Redis", [
                    'log_id' => $log->id,
                    'session' => $session,
                    'to' => $log->to
                ]);

            } catch (\Exception $e) {
                $this->error("Erro ao recuperar log {$log->id}: " . $e->getMessage());
                Log::error("Recovery Command Failure for ID {$log->id}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Recuperação concluída.");
    }
}
