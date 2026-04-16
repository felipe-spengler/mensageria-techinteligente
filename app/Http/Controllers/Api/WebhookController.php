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
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages', json_encode([
                'to' => $dest,
                'message' => $message,
                'session' => 'mensageria-tech', // Admin session
            ]));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failure notification failed: ' . $e->getMessage());
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

        \App\Models\WhatsappInstance::where('session_name', $request->session)
            ->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
