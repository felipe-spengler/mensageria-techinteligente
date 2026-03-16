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
            $messageCount = MessageLog::where('api_key_id', $apiKey->id)
                ->whereMonth('created_at', now()->month)
                ->count();

            if ($apiKey->plan->message_limit > 0 && $messageCount >= $apiKey->plan->message_limit) {
                return response()->json(['error' => 'Message limit reached for your plan.'], 403);
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
            Log::error('Redis Error: ' . $e->getMessage());
        }
    }
}
