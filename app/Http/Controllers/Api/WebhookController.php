<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function status(Request $request)
    {
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
