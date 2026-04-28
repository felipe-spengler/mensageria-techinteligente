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
        // Pega mensagens em 'queued' (paradas) OU 'failed' por erro de sistema/timeout
        $stuckMessages = MessageLog::where(function($query) {
                $query->where('status', 'queued')
                      ->orWhere(function($q) {
                          $q->where('status', 'failed')
                            ->where(function($sq) {
                                $sq->where('error_message', 'like', '%timeout%')
                                  ->orWhere('error_message', 'like', '%Connection%')
                                  ->orWhere('error_message', 'like', '%refused%');
                            })
                            ->where('updated_at', '>', now()->subHours(1)); // Mensagens com falha recente
                      });
            })
            ->where('updated_at', '<', now()->subMinutes(15))
            ->get();

        if ($stuckMessages->isEmpty()) {
            return;
        }

        $this->info("Encontradas {$stuckMessages->count()} mensagens travadas. Iniciando recuperação...");

        // Pegar hora atual de Brasília
        $nowSp = now()->setTimezone('America/Sao_Paulo');
        $hour = $nowSp->hour;
        $day = $nowSp->dayOfWeek;
        $isBusinessHours = ($day >= 1 && $day <= 5 && $hour >= 8 && $hour < 18);

        foreach ($stuckMessages as $log) {
            try {
                $session = null;
                
                // Tenta encontrar a instância do usuário para saber qual fila usar
                if ($log->apiKey && $log->apiKey->user_id) {
                    $instance = WhatsappInstance::where('user_id', $log->apiKey->user_id)->first();
                    if ($instance) {
                        $session = $instance->session_name;
                    }
                }

                if (!$session) {
                    $this->warn("Ignorando ID {$log->id}: Instância de WhatsApp não encontrada para o usuário.");
                    continue;
                }

                // Verifica se a instância possui restrição de horário e se estamos fora dele
                if (isset($instance) && $instance->schedule_type === 'business_hours' && !$isBusinessHours) {
                    // Toca o updated_at para que o agendador não a reprocesse enquanto aguarda o horário comercial
                    $log->touch();
                    continue;
                }

                $this->line("Re-enviando ID {$log->id} para a fila: {$session}");

                Redis::rpush('wpp_messages:' . $session, json_encode([
                    'log_id' => $log->id,
                    'to' => $log->to,
                    'message' => $log->message,
                    'media' => $log->media_url,
                    'session' => $session
                ]));

                // Se era uma falha anterior, volta o status para queued no banco
                if ($log->status === 'failed') {
                    $log->update(['status' => 'queued', 'error_message' => 'Retrying after system failure alert']);
                } else {
                    $log->touch(); // Atualiza o updated_at para não enviar repetidamente enquanto está na fila
                }

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

            // Busca a instância do admin (ID 1) para enviar notificações do sistema
            $adminInstance = WhatsappInstance::where('user_id', 1)->first();
            $session = $adminInstance ? $adminInstance->session_name : null;

            if (!$session) {
                Log::error('Expiry Notification Error: Nenhuma instância administrativa (User 1) encontrada.');
                return;
            }

            Redis::rpush('wpp_messages:' . $session, json_encode([
                'to' => $redisTo,
                'message' => $message,
                'is_system_notification' => true,
                'session' => $session
            ]));
        } catch (\Exception $e) {
            Log::error('Expiry Notification Redis Error: ' . $e->getMessage());
        }
    }
}
