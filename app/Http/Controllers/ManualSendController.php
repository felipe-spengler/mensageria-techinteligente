<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\PixTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ManualSendController extends Controller
{
    public function index(Request $request)
    {
        $ip = $request->ip();
        $hasUsedFree = MessageLog::where('ip_address', $ip)->where('is_free', true)->exists();
        
        return view('manual-send', compact('hasUsedFree'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to' => 'required|string', // Pode ser CSV
            'message' => 'required',
            'media' => 'nullable|string',
        ]);

        $ip = $request->ip();
        $hasUsedFree = MessageLog::where('ip_address', $ip)->where('is_free', true)->exists();
        
        $recipients = explode(',', $request->to);
        $recipients = array_map('trim', $recipients);
        $recipients = array_filter($recipients);

        // Se for teste grátis (apenas 1 número)
        if (!$hasUsedFree && count($recipients) === 1) {
            $log = MessageLog::create([
                'to' => $recipients[0],
                'message' => $request->message,
                'media_url' => $request->media,
                'status' => 'queued',
                'ip_address' => $ip,
                'is_free' => true,
            ]);

            $this->pushToQueue($log);

            return response()->json([
                'success' => true,
                'type' => 'free',
                'message' => 'Mensagem de teste enviada com sucesso!'
            ]);
        }

        // Caso contrário, gerar PIX de R$ 5,00 (pacote de 5 envios)
        $txid = Str::random(20);
        $amount = 5.00; 

        $transaction = PixTransaction::create([
            'amount' => $amount,
            'status' => 'pending',
            'txid' => $txid,
            'metadata' => [
                'recipients' => $recipients,
                'message' => $request->message,
                'media' => $request->media,
                'ip_address' => $ip,
            ]
        ]);

        return response()->json([
            'success' => true,
            'type' => 'paid',
            'pix_code' => '00020126580014br.gov.bcb.pix...', // Mock PIX code
            'txid' => $txid,
            'transaction_id' => $transaction->id
        ]);
    }

    public function checkStatus($txid)
    {
        $transaction = PixTransaction::where('txid', $txid)->firstOrFail();
        
        // Simulação: Se for mock, vamos "pagar" automaticamente após 10 segundos ou ao consultar
        // No mundo real, aqui esperaria o Webhook da Asaas
        if ($transaction->status === 'pending') {
            // Logica temporária para testes: Aprova qualquer transação consultada
            $transaction->update(['status' => 'paid']);
            
            // Criar as mensagens
            foreach ($transaction->metadata['recipients'] as $to) {
                $log = MessageLog::create([
                    'to' => $to,
                    'message' => $transaction->metadata['message'],
                    'media_url' => $transaction->metadata['media'],
                    'status' => 'queued',
                    'ip_address' => $transaction->metadata['ip_address'],
                    'is_free' => false,
                ]);

                $this->pushToQueue($log);
            }
        }

        return response()->json([
            'status' => $transaction->status,
        ]);
    }

    public function getBridgeQrCode()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://bridge:3000/qrcode');
            return response($response->body(), 200)
                ->header('Content-Type', 'image/png');
        } catch (\Exception $e) {
            return response('Bridge offline', 503);
        }
    }

    public function getBridgeStatus()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://bridge:3000/status');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['status' => 'offline'], 503);
        }
    }

    private function pushToQueue($log)
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages', json_encode([
                'log_id' => $log->id,
                'to' => $log->to,
                'message' => $log->message,
                'media' => $log->media_url,
            ]));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar para o Redis: ' . $e->getMessage());
        }
    }
}
