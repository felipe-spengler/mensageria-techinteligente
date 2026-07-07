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
                            ->where('updated_at', '>', now('America/Sao_Paulo')->subHours(1)); // Mensagens com falha recente
                      });
            })
            ->where('updated_at', '<', now('America/Sao_Paulo')->subMinutes(15))
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
                $instance = $log->instance;
                if ($instance) {
                    $session = $instance->session_name;
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

                // --- BLINDAGEM: Verifica se o ID já está no Redis ---
                $lockKey = "wpp_enqueued:{$log->id}";
                if (Redis::exists($lockKey)) {
                    $this->warn("ID {$log->id} já está no Redis (enqueued). Pulando para evitar duplicata.");
                    continue;
                }

                $this->line("Re-enviando ID {$log->id} para a fila: {$session}");

                // Mantém o cache do Redis atualizado
                if (isset($instance) && $instance->schedule_type) {
                    Redis::set('wpp_instance:schedule:' . $session, $instance->schedule_type, 'EX', 3600);
                }

                Redis::rpush('wpp_messages:' . $session, json_encode([
                    'log_id' => $log->id,
                    'to' => $log->to,
                    'message' => $log->message,
                    'media' => $log->media_url,
                    'session' => $session,
                    'schedule_type' => $instance->schedule_type ?? 'full_time'
                ]));

                // Marca como "em fila" no Redis (Expira em 48 horas para evitar duplicação em offline longo)
                Redis::set($lockKey, '1', 'EX', 172800);


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
        $cacheKey = 'expiry_warnings_sent:' . now()->format('Y-m-d');
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $this->info("Avisos de vencimento já foram processados hoje.");
            return;
        }

        // 1. Planos que vencem em exatamente 5 dias
        $this->info("Verificando planos que vencem em 5 dias...");
        $expiringIn5 = \App\Models\ApiKey::where('status', 'active')
            ->whereDate('expires_at', now()->addDays(5)->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($expiringIn5 as $key) {
            if (!$key->user || !$key->user->phone) continue;

            $message = "📢 *Aviso TechInteligente*\n\nOlá, *{$key->user->name}*!\n\nPassando para avisar que sua assinatura do plano *{$key->plan->name}* vence em *5 dias*.\n\nEvite interrupções no seu serviço garantindo a renovação diretamente no painel.\n\nVocê também pode realizar a renovação via PIX direto (sem taxas) pela chave celular:\n🔑 *49999459490*\nApós realizar o pagamento, basta enviar o comprovante aqui e reativaremos na hora!\n\n_Acesse o painel: " . config('app.url') . "/admin_";

            $this->pushRawToAdminQueue($key->user->phone, $message);
            $this->line("Aviso de 5 dias enviado para {$key->user->name}");
        }

        // 2. Planos que vencem em exatamente 1 dia (amanhã)
        $this->info("Verificando planos que vencem em 1 dia...");
        $expiringIn1 = \App\Models\ApiKey::where('status', 'active')
            ->whereDate('expires_at', now()->addDays(1)->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($expiringIn1 as $key) {
            if (!$key->user || !$key->user->phone) continue;

            $message = "🚨 *Aviso Importante - TechInteligente*\n\nOlá, *{$key->user->name}*!\n\nSua assinatura do plano *{$key->plan->name}* vence *AMANHÃ*!\n\nPara que suas mensagens não parem de ser enviadas, realize a renovação agora mesmo.\n\nVocê pode pagar via PIX direto (sem taxas) pela chave celular:\n🔑 *49999459490*\nApós o envio, nos mande o comprovante por aqui e faremos a ativação manual imediata!\n\n_Acesse o painel: " . config('app.url') . "/admin_";

            $this->pushRawToAdminQueue($key->user->phone, $message);
            $this->line("Aviso de 1 dia enviado para {$key->user->name}");
        }

        // Grava no cache por 24h para não duplicar hoje
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));
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
                'session' => $session,
                'schedule_type' => $adminInstance->schedule_type ?? 'full_time'
            ]));

            // Mantém o cache do Redis atualizado
            if (isset($adminInstance) && $adminInstance->schedule_type) {
                Redis::set('wpp_instance:schedule:' . $session, $adminInstance->schedule_type, 'EX', 3600);
            }
        } catch (\Exception $e) {
            Log::error('Expiry Notification Redis Error: ' . $e->getMessage());
        }
    }
}
