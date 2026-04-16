<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\PixTransaction;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ManualSendController extends Controller
{
    use FormatsPhoneNumber;
    public function index(Request $request)
    {
        $ip = $request->ip();
        $hasUsedFree = MessageLog::where('ip_address', $ip)->where('is_free', true)->exists();
        
        return view('manual-send', compact('hasUsedFree'));
    }

    public function showEnviaApi()
    {
        Log::info('Envia API page acessada');
        return view('envia-api');
    }

    public function postEnviaApi(Request $request)
    {
        $apiKey = ApiKey::where('key', 'test_key_master_123')->first();

        if (!$apiKey) {
            Log::error('Envia API: api key teste não encontrada');
            return response()->json(['error' => 'API key não encontrada'], 500);
        }

        $payload = [
            'to' => '45920014605',
            'message' => 'testando',
            'media' => null,
        ];

        Log::info('Envia API: enviando requisição /api/v1/send', ['payload' => $payload, 'api_key_id' => $apiKey->id]);

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->key,
            'Accept' => 'application/json',
        ])->post(url('/api/v1/send'), $payload);

        $body = $response->json();
        Log::info('Envia API: resposta recebida', ['status' => $response->status(), 'body' => $body]);

        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'response' => $body,
        ], $response->status());
    }

    public function store(Request $request)
    {
        $requestId = (string) Str::uuid();

        try {
            $payload = [
                'request_id' => $requestId,
                'to' => $request->to,
                'message_length' => strlen($request->message ?? ''),
                'has_media' => !empty($request->media),
                'from_ip' => $request->ip(),
            ];
            Log::info('Manual send request received', $payload);

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

            $formattedRecipients = [];
            foreach ($recipients as $rawTo) {
                $to = $this->formatBrazilianNumber($rawTo);
                if (!$to) {
                    return response()->json(['success' => false, 'message' => "O número {$rawTo} é inválido. DDD e número necessários."], 422);
                }
                $formattedRecipients[] = $to;
            }
            $recipients = $formattedRecipients;

            if (count($recipients) === 0) {
                Log::warning('Manual send validation failed: empty recipients', ['payload' => $payload]);
                return response()->json(['success' => false, 'message' => 'Nenhum destinatário válido informado.'], 422);
            }

            // Se for teste grátis (apenas 1 número)
            if (!$hasUsedFree && count($recipients) === 1) {
                Log::debug('Manual send applying free quota', ['recipient' => $recipients[0]]);
                $log = MessageLog::create([
                    'to' => $recipients[0],
                    'message' => $request->message,
                    'media_url' => $request->media,
                    'status' => 'queued',
                    'ip_address' => $ip,
                    'is_free' => true,
                ]);

                if (!$this->pushToQueue($log)) {
                    Log::warning('Falha ao enfileirar mensagem de teste grátis', ['log_id' => $log->id]);
                    $log->update(['status' => 'failed']);

                    return response()->json([
                        'success' => false,
                        'message' => 'Não foi possível enviar. Tente novamente em alguns segundos.',
                        'debug' => ['stage' => 'queue_push', 'log_id' => $log->id]
                    ], 500);
                }

                $responsePayload = [
                    'request_id' => $requestId,
                    'success' => true,
                    'type' => 'free',
                    'message' => 'Mensagem de teste enviada com sucesso!',
                    'debug' => ['stage' => 'queued', 'log_id' => $log->id]
                ];

                Log::info('Manual send free response', $responsePayload);
                return response()->json($responsePayload);
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

            $responsePayload = [
                'request_id' => $requestId,
                'success' => true,
                'type' => 'paid',
                'pix_code' => '00020126580014br.gov.bcb.pix...', // Mock PIX code
                'txid' => $txid,
                'transaction_id' => $transaction->id,
                'debug' => ['stage' => 'transaction_created', 'txid' => $txid]
            ];
            Log::info('Manual send paid transaction response', $responsePayload);
            return response()->json($responsePayload);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Manual send validation failed', ['errors' => $e->errors(), 'payload' => $payload, 'request_id' => $requestId]);
            return response()->json([
                'request_id' => $requestId,
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
                'debug' => ['stage' => 'validation', 'payload' => $payload]
            ], 422);
        } catch (\Exception $e) {
            Log::error('Manual send exception', ['exception' => $e, 'payload' => $payload, 'request_id' => $requestId]);
            return response()->json([
                'request_id' => $requestId,
                'success' => false,
                'message' => 'Erro interno no servidor: ' . $e->getMessage(),
                'debug' => ['stage' => 'exception', 'exception' => $e->getMessage()]
            ], 500);
        }
    }

    public function checkStatus($txid)
    {
        $transaction = PixTransaction::where('txid', $txid)->firstOrFail();
        
        // Retorna apenas o status real da transação
        // O pagamento será processado via webhook do Asaas (AsaasWebhookController)
        return response()->json([
            'status' => $transaction->status,
        ]);
    }

    private function buildBridgeUrls(): array
    {
        $urls = [
            env('WPP_BRIDGE_URL'),
            'http://bridge:3000',
            'http://127.0.0.1:3000',
            'http://localhost:3000',
        ];

        return array_values(array_filter($urls));
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }

    private function requestBridge(string $path)
    {
        $lastError = null;

        foreach ($this->buildBridgeUrls() as $base) {
            $url = rtrim($base, '/') . '/' . ltrim($path, '/');
            try {
                \Illuminate\Support\Facades\Log::debug('Bridge request starting', ['path' => $path, 'url' => $url]);
                $response = Http::timeout(5)->get($url);
                if ($response->successful()) {
                    \Illuminate\Support\Facades\Log::debug('Bridge request succeeded', ['path' => $path, 'url' => $url, 'status' => $response->status()]);
                    return [$response, $url];
                }

                $lastError = "HTTP " . $response->status();
                \Illuminate\Support\Facades\Log::warning('Bridge request returned non-success status', ['path' => $path, 'url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                \Illuminate\Support\Facades\Log::error('Bridge request exception', ['path' => $path, 'url' => $url, 'exception' => $e]);
            }

            \Illuminate\Support\Facades\Log::warning("Bridge request failed [{$path}] {$url}: {$lastError}");
        }

        throw new \RuntimeException('Bridge unreachable: ' . ($lastError ?: 'no URLs available'));
    }

    public function getBridgeQrCode(Request $request)
    {
        $user = Auth::user();
        $instance = \App\Models\WhatsappInstance::where('user_id', $user->id)->first();
        
        if (!$instance) return response('No instance', 404);

        try {
            [$response, $url] = $this->requestBridge('qrcode/' . $instance->session_name);
            return response($response->body(), 200)
                ->header('Content-Type', 'image/png');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('QR Code Error: ' . $e->getMessage());
            return response('Bridge offline or unreachable: ' . $e->getMessage(), 503);
        }
    }

    public function getBridgeStatus()
    {
        $user = Auth::user();
        $instance = \App\Models\WhatsappInstance::where('user_id', $user->id)->first();
        
        if (!$instance) return response()->json(['status' => 'offline']);

        try {
            [$response, $url] = $this->requestBridge('status/' . $instance->session_name);
            $payload = $response->json();
            \Illuminate\Support\Facades\Log::debug('Bridge status payload', ['payload' => $payload]);
            return response()->json($payload);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Status Error', ['exception' => $e]);
            return response()->json([
                'status' => 'offline',
                'error' => $e->getMessage(),
                'url' => env('WPP_BRIDGE_URL', 'http://bridge:3000') . '/status'
            ], 503);
        }
    }

    public function getBridgeHealth()
    {
        try {
            $requestId = (string) Str::uuid();
            $bridgeStatus = 'unknown';
            $bridgeData = null;
            $redisStatus = 'unknown';
            $redisInfo = null;

            try {
                [$response,] = $this->requestBridge('status');
                $bridgeData = $response->json();
                $bridgeStatus = $bridgeData['status'] ?? 'unknown';
            } catch (\Exception $e) {
                $bridgeStatus = 'offline';
                $bridgeData = ['error' => $e->getMessage()];
            }

            try {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $redisInfo = $redis->info();
                $redisStatus = 'online';
            } catch (\Throwable $e) {
                $redisStatus = 'offline';

                $baseHint = 'Verifique REDIS_CLIENT e as extensões PHP (ext-redis) ou uso de predis.';
                if (config('database.redis.client') === 'phpredis' && !extension_loaded('redis')) {
                    $baseHint = 'REDIS_CLIENT está configurado como phpredis mas ext-redis não está carregada. Ajuste para predis ou instale ext-redis.';
                }

                $redisInfo = [
                    'error' => $e->getMessage(),
                    'hint' => $baseHint,
                    'config_client' => config('database.redis.client'),
                    'php_redis_loaded' => extension_loaded('redis'),
                    'php_predis_available' => class_exists('Predis\Client'),
                ];
            }

            $result = [
                'request_id' => $requestId,
                'bridge' => ['status' => $bridgeStatus, 'details' => $bridgeData],
                'redis' => ['status' => $redisStatus, 'details' => $redisInfo],
                'timestamp' => now()->toIso8601String(),
            ];

            Log::info('Bridge health check', array_merge($result));

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Bridge health unexpected error', ['exception' => $e]);
            return response()->json([
                'request_id' => (string) Str::uuid(),
                'bridge' => ['status' => 'error', 'details' => ['message' => $e->getMessage()]],
                'redis' => ['status' => 'error', 'details' => ['message' => $e->getMessage()]],
                'timestamp' => now()->toIso8601String(),
                'error' => 'Health check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function pushToQueue($log): bool
    {
        try {
            $to = $log->to;
            
            // Determine session
            $session = 'mensageria-tech'; // fallback
            if ($log->apiKey && $log->apiKey->user) {
                $instance = \App\Models\WhatsappInstance::where('user_id', $log->apiKey->user_id)->first();
                if ($instance) $session = $instance->session_name;
            } elseif ($log->user_id) {
                $instance = \App\Models\WhatsappInstance::where('user_id', $log->user_id)->first();
                if ($instance) $session = $instance->session_name;
            }

            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->rpush('wpp_messages:' . $session, json_encode([
                'log_id' => $log->id,
                'to' => $to,
                'message' => $log->message,
                'media' => $log->media_url,
                'session' => $session,
            ]));

            return true;
        } catch (\Throwable $e) {
            $hint = 'Verifique a configuração de redis e a extensão PHP.';
            if (config('database.redis.client') === 'phpredis' && !extension_loaded('redis')) {
                $hint = 'REDIS_CLIENT=phpredis mas ext-redis não está instalada (local ou VPS). Use REDIS_CLIENT=predis ou instale ext-redis.';
            }
            Log::error('Erro ao enviar para o Redis: ' . $e->getMessage(), [
                'request_id' => $log->id ?? null,
                'redis_client' => config('database.redis.client'),
                'php_redis_loaded' => extension_loaded('redis'),
                'predis_available' => class_exists('Predis\Client'),
                'hint' => $hint,
            ]);
            return false;
        }
    }
}
