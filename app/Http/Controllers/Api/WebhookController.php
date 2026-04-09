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
        $log->update([
            'status' => $request->status,
            'error_message' => $request->error_message,
            'sent_at' => $request->status === 'sent' ? now() : null,
        ]);

        return response()->json(['success' => true]);
    }
}
