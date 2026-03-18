<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
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

        // Get API Key from Middleware
        $apiKey = $request->attributes->get('api_key');

        // Check plan limits (Usage in current month)
        if ($apiKey->plan) {
            // 1. Check if trying to send Media but plan is only Text
            if (!empty($request->media) && $apiKey->plan->type !== 'media') {
                $this->notifyUpgradeForMedia($apiKey);
                return response()->json([
                    'error' => 'Your current plan does not support media (Images/PDF). Please upgrade to a Media plan.',
                    'current_plan' => $apiKey->plan->name
                ], 403);
            }

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

        $message = "📢 *Aviso de Uso*\n\nVocê já utilizou *{$count}* mensagens este mês. Isso representa *90%* do seu limite no plano *{$apiKey->plan->name}*.\n\nGaranta que seu serviço não seja interrompido fazendo um upgrade agora!";
        
        $this->pushRawToQueue($apiKey->user->wpp_phone, $message);
    }

    private function pushRawToQueue($to, $message)
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages', json_encode([
                'to' => $to,
                'message' => $message,
                'is_system_notification' => true
            ]));
        } catch (\Exception $e) {
            Log::error('Notification Redis Error: ' . $e->getMessage());
        }
    }

    private function pushToQueue($log)
    {
        try {
            // Garante o prefixo 55 se o usuário não digitar
            $to = preg_replace('/\D/', '', $log->to);
            if (!empty($to) && !str_starts_with($to, '55')) {
                $to = '55' . $to;
                $log->update(['to' => $to]);
            }

            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages', json_encode([
                'log_id' => $log->id,
                'to' => $to,
                'message' => $log->message,
                'media' => $log->media_url,
            ]));
        } catch (\Exception $e) {
            Log::error('Redis Error: ' . $e->getMessage());
        }
    }
}
