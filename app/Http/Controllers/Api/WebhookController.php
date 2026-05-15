<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function status(Request $request)
    {
        // Simple internal authentication
        $authHeader = $request->header('Authorization');
        $internalKey = config('app.internal_key', env('INTERNAL_KEY', '7caeb868-3d08-4761-b126-4f601cd05f7a'));
        
        if ($authHeader !== 'Bearer ' . $internalKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'log_id' => 'required|exists:message_logs,id',
            'status' => 'required|in:sent,failed',
            'error_message' => 'nullable|string',
        ]);

        $log = MessageLog::find($request->log_id);
        $oldStatus = $log->status;
        
        $log->update([
            'status' => $request->status,
            'error_message' => $request->error_message,
            'sent_at' => $request->status === 'sent' ? now() : null,
        ]);

        // --- NOTIFICAÇÃO DO CLIENTE (WEBHOOK CUSTOMIZADO) ---
        $this->notifyClientWebhook($log);

        // If it failed, notify the user via Admin WhatsApp
        if ($request->status === 'failed' && $oldStatus !== 'failed') {
            $this->notifyUserOfFailure($log);
        }

        return response()->json(['success' => true]);
    }

    private function notifyUserOfFailure($log)
    {
        // Get user
        $user = null;
        if ($log->apiKey) {
            $user = $log->apiKey->user;
        } elseif ($log->user_id) {
            $user = \App\Models\User::find($log->user_id);
        }

        if (!$user || !$user->phone) return;

        $dest = preg_replace('/[^0-9]/', '', $user->phone);
        if (strlen($dest) < 10) return;

        $message = "❌ *Erro de Entrega TechInteligente*\n\nIdentificamos uma falha ao enviar sua mensagem para: *{$log->to}*.\n\n*Erro:* {$log->error_message}\n\n_Verifique se seu dispositivo está conectado e se o número de destino é válido._";

        try {
            // Busca a instância do admin (ID 1) para enviar notificações do sistema
            $adminInstance = \App\Models\WhatsappInstance::where('user_id', 1)->first();
            $session = $adminInstance ? $adminInstance->session_name : null;

            if (!$session) {
                \Illuminate\Support\Facades\Log::error('Failure Notification Error: Nenhuma instância administrativa (User 1) encontrada.');
                return;
            }

            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages:' . $session, json_encode([
                'to' => $dest,
                'message' => $message,
                'session' => $session,
                'schedule_type' => $adminInstance->schedule_type ?? 'all_hours'
            ]));

            // Atualiza o cache do Redis sobre o tipo de agenda desta sessão para o motor saber
            if ($adminInstance->schedule_type) {
                $redis->set('wpp_instance:schedule:' . $session, $adminInstance->schedule_type, 'EX', 3600);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failure notification failed: ' . $e->getMessage());
        }
    }

    private function notifyClientWebhook($log)
    {
        try {
            // Busca o usuário dono da API Key
            $user = $log->apiKey->user;

            if ($user && $user->webhook_url) {
                // Busca a instância para saber a sessão (opcional, para informação)
                $instance = \App\Models\WhatsappInstance::where('user_id', $user->id)->first();

                \Illuminate\Support\Facades\Http::timeout(5)
                    ->withoutVerifying()
                    ->post($user->webhook_url, [
                        'event' => 'message.status',
                        'data' => [
                            'id' => $log->id,
                            'to' => $log->to,
                            'status' => $log->status,
                            'error' => $log->error_message,
                            'sent_at' => $log->sent_at,
                            'message' => $log->message,
                            'session' => $instance->session_name ?? 'unknown'
                        ]
                    ]);
                
                \Illuminate\Support\Facades\Log::info("Webhook enviado para {$user->webhook_url} (Log ID: {$log->id})");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Falha ao enviar webhook do cliente: " . $e->getMessage());
        }
    }

    public function instanceStatus(Request $request)
    {
        $authHeader = $request->header('Authorization');
        $internalKey = config('app.internal_key', env('INTERNAL_KEY', '7caeb868-3d08-4761-b126-4f601cd05f7a'));
        
        if ($authHeader !== 'Bearer ' . $internalKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'session' => 'required|string',
            'status' => 'required|string',
        ]);

        $instance = \App\Models\WhatsappInstance::where('session_name', $request->session)->first();
        if ($instance) {
            $instance->update(['status' => $request->status]);
        }

        return response()->json(['success' => true]);
    }
}
