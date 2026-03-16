<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'message' => 'required|string',
            'media' => 'nullable|string', // Can be URL or Base64
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $apiKeyHeader = $request->header('Authorization');
        if (!$apiKeyHeader || !str_starts_with($apiKeyHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $key = str_replace('Bearer ', '', $apiKeyHeader);
        $apiKey = ApiKey::where('key', $key)->where('status', 'active')->first();

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid or inactive API Key'], 401);
        }

        // Check plan limits (simple count for now)
        $messageCount = $apiKey->messageLogs()->whereMonth('created_at', now()->month)->count();
        if ($messageCount >= $apiKey->plan->message_limit) {
            return response()->json(['error' => 'Plan limit reached'], 403);
        }

        // Create log entry
        $log = MessageLog::create([
            'api_key_id' => $apiKey->id,
            'to' => $request->to,
            'message' => $request->message,
            'media_url' => $request->media,
            'status' => 'queued',
        ]);

        // TODO: Dispatch to Redis/SQS for Node.js worker to pick up

        return response()->json([
            'success' => true,
            'message' => 'Message queued successfully',
            'log_id' => $log->id
        ]);
    }
}
