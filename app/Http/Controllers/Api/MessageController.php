<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\MessageLog;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    use FormatsPhoneNumber, \App\Traits\InteractsWithBridge;
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'message' => 'required|string',
            'media' => 'nullable|string', 
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // --- Intelligent Phone Number Formatting ---
        $to = $this->formatBrazilianNumber($request->to);
        
        if (!$to) {
            return response()->json(['error' => 'Número inválido. Use DDD + 8 ou 9 dígitos.'], 422);
        }
        
        $request->merge(['to' => $to]); // Update request data

        // Get API Key from Middleware
        $apiKey = $request->attributes->get('api_key');

        // Check plan limits (Usage in current month)
        if ($apiKey->plan) {
            // 0. Check Schedule
            $instance = \App\Models\WhatsappInstance::where('user_id', $apiKey->user_id)->first();
            if ($instance && $instance->schedule_type === 'business_hours') {
                $now = now();
                $hour = $now->hour;
                $day = $now->dayOfWeek; // 0 (Sun) to 6 (Sat)
                
                if ($day === 0 || $day === 6 || $hour < 8 || $hour >= 18) {
                    return response()->json([
                        'error' => 'Fora do horário comercial. Sua instância está configurada para enviar apenas de Seg-Sex das 08h às 18h.',
                    ], 403);
                }
            }

            // 1. Check if trying to send Media but plan is only Text

            // 2. Check message limit
            $messageCount = MessageLog::where('api_key_id', $apiKey->id)
                ->whereMonth('created_at', now()->month)
                ->count();

            if ($apiKey->plan->message_limit > 0 && $messageCount >= $apiKey->plan->message_limit) {
                $this->notifyLimitReached($apiKey);
                return response()->json(['error' => 'Message limit reached for your plan.'], 403);
            }

            // Alert at 90% usage
            if ($apiKey->plan->message_limit > 0 && $messageCount == floor($apiKey->plan->message_limit * 0.9)) {
                $this->notifyLimitWarning($apiKey, $messageCount);
            }
        }

        // Create log entry
        $log = MessageLog::create([
            'api_key_id' => $apiKey->id,
            'to' => $request->to,
            'message' => $request->message,
            'media_url' => $request->media,
            'status' => 'queued',
        ]);

        // Push to Redis for Node.js worker
        $this->pushToQueue($log);

        return response()->json([
            'success' => true,
            'message' => 'Message queued successfully',
            'log_id' => $log->id,
            'remaining' => $apiKey->plan ? (max(0, $apiKey->plan->message_limit - ($messageCount + 1))) : 'unlimited'
        ]);
    }

    public function qrcode(Request $request)
    {
        $apiKey = $request->attributes->get('api_key');
        $instance = \App\Models\WhatsappInstance::where('user_id', $apiKey->user_id)->first();
        
        if (!$instance) return response()->json(['error' => 'No instance found for this user'], 404);

        try {
            [$response, $url] = $this->requestBridge('qrcode/' . $instance->session_name);
            
            if ($response->status() === 404) {
                return response()->json([
                    'error' => 'QR Code not ready. Ensure instance is starting.',
                    'session_status' => $response->json()['sessionStatus'] ?? 'unknown'
                ], 404);
            }

            return response($response->body(), 200)
                ->header('Content-Type', 'image/png');
        } catch (\Exception $e) {
            Log::error('API QR Code Error: ' . $e->getMessage());
            return response()->json(['error' => 'Bridge offline or unreachable'], 503);
        }
    }

    public function logs(Request $request)
    {
        $apiKey = $request->attributes->get('api_key');
        
        $logs = MessageLog::where('api_key_id', $apiKey->id)
            ->latest()
            ->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    private function notifyUpgradeForMedia($apiKey)
    {
        if (!$apiKey->user || !$apiKey->user->phone) return;

        $message = "📸 *Recurso Bloqueado*\n\nIdentificamos uma tentativa de envio de *mídia (arquivo/imagem)*, mas seu plano atual (*{$apiKey->plan->name}*) é restrito a *apenas texto*.\n\nLibere o envio de mídias agora mesmo fazendo um upgrade para a categoria +Mídia!\n\n_Veja os planos aqui: https://mensagens.techinteligente.site/precos_";
        
        $this->pushRawToQueue($apiKey->user->wpp_phone, $message);
    }

    private function notifyLimitReached($apiKey)
    {
        if (!$apiKey->user || !$apiKey->user->phone) return;

        $message = "⚠️ *Aviso TechInteligente*\n\nSeu limite de mensagens para o plano *{$apiKey->plan->name}* foi atingido (100%).\n\nNovos disparos via API serão bloqueados até a renovação ou upgrade.\n\n_Acesse o painel para gerenciar sua assinatura._";
        
        $this->pushRawToQueue($apiKey->user->wpp_phone, $message);
    }

    private function notifyLimitWarning($apiKey, $count)
    {
        if (!$apiKey->user || !$apiKey->user->phone) return;

        $message = "📢 *Aviso de Uso TechInteligente*\n\nVocê já utilizou *{$count}* mensagens este mês. Isso representa *90%* do seu limite no plano *{$apiKey->plan->name}*.\n\nGaranta que seu serviço não seja interrompido fazendo um upgrade agora!";
        
        $this->pushRawToQueue($apiKey->user->wpp_phone, $message);
    }

    private function pushRawToQueue($to, $message)
    {
        try {
            $redisTo = $this->formatBrazilianNumber($to);
            if (!$redisTo) return; // Skip if invalid

            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages:mensageria-tech', json_encode([
                'to' => $redisTo,
                'message' => $message,
                'is_system_notification' => true,
                'session' => 'mensageria-tech' // Admin session
            ]));
        } catch (\Exception $e) {
            Log::error('Notification Redis Error: ' . $e->getMessage());
        }
    }

    private function pushToQueue($log)
    {
        try {
            // O número já vem formatado do controller
            $to = $log->to;

            $redis = \Illuminate\Support\Facades\Redis::connection();
            
            $session = 'mensageria-tech';
            $instance = \App\Models\WhatsappInstance::where('user_id', $log->apiKey->user_id)->first();
            
            if ($instance) {
                $session = $instance->session_name;

                // --- AUTO-START LOGIC ---
                // Se a instância não está ativa (connected), avisamos o bridge para tentar ligar
                // Isso garante que se o servidor reiniciar, o primeiro envio de API "acorde" a instância.
                if ($instance->status !== 'connected' && $instance->status !== 'qr_ready') {
                    $this->triggerBridgeStart($session);
                }
            }

            $redis->rpush('wpp_messages:' . $session, json_encode([
                'log_id' => $log->id,
                'to' => $to,
                'message' => $log->message,
                'media' => $log->media_url,
                'session' => $session
            ]));
        } catch (\Exception $e) {
            Log::error('Redis Error: ' . $e->getMessage());
        }
    }

    private function triggerBridgeStart($session)
    {
        try {
            // Chamada "fire and forget" ou com timeout curto para não travar a resposta da API
            $bridgeUrl = env('WPP_BRIDGE_URL', 'http://bridge:3000');
            \Illuminate\Support\Facades\Http::timeout(5)->post($bridgeUrl . '/start/' . $session);
        } catch (\Exception $e) {
             Log::warning("Auto-start bridge failure for session {$session}: " . $e->getMessage());
        }
    }
}
