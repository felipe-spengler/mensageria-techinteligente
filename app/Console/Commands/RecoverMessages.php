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
        $this->processRecovery();
        
        // Uma vez por dia (ou quando rodar entre 09:00 e 09:59), verifica vencimentos
        if (now()->hour == 9) {
            $this->processExpiryWarnings();
        }
    }

    private function processRecovery()
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

    private function processExpiryWarnings()
    {
        $this->info("Verificando planos que vencem em 10 dias...");
        
        // Busca chaves que vencem em exatamente 10 dias
        $expiringKeys = \App\Models\ApiKey::where('status', 'active')
            ->whereDate('expires_at', now()->addDays(10)->toDateString())
            ->with('user')
            ->get();

        foreach ($expiringKeys as $key) {
            if (!$key->user || !$key->user->phone) continue;

            $message = "📢 *Aviso TechInteligente*\n\nOlá, *{$key->user->name}*!\n\nPassando para avisar que sua assinatura do plano *{$key->plan->name}* vence em *10 dias*.\n\nEvite interrupções no seu serviço garantindo a renovação diretamente no painel.\n\n_Acesse aqui: " . config('app.url') . "/admin_";

            $this->pushRawToAdminQueue($key->user->phone, $message);
            $this->line("Aviso de 10 dias enviado para {$key->user->name}");
        }
    }

    private function pushRawToAdminQueue($to, $message)
    {
        try {
            $redisTo = $to;
            // Limpa caracteres não numéricos
            $redisTo = preg_replace('/[^0-9]/', '', $redisTo);
            
            // Garante DDI 55
            if (strlen($redisTo) <= 11) {
                $redisTo = '55' . $redisTo;
            }

            Redis::rpush('wpp_messages:mensageria-tech', json_encode([
                'to' => $redisTo,
                'message' => $message,
                'is_system_notification' => true,
                'session' => 'mensageria-tech'
            ]));
        } catch (\Exception $e) {
            Log::error('Expiry Notification Redis Error: ' . $e->getMessage());
        }
    }
}
