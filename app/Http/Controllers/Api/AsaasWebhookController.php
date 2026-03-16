<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\PixTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $payment = $request->input('payment');

        Log::info('Asaas Webhook Received', ['event' => $event, 'payment_id' => $payment['id'] ?? null]);

        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                $this->processPayment($payment);
                break;
            
            case 'PAYMENT_OVERDUE':
                // Handle overdue payment (suspend API Key etc)
                break;
        }

        return response()->json(['success' => true]);
    }

    protected function processPayment($payment)
    {
        // 1. Check if it's a manual PIX (B2C)
        $transaction = PixTransaction::where('txid', $payment['externalReference'] ?? '')->first();
        if ($transaction) {
            $transaction->update(['status' => 'paid']);
            
            // Trigger the message sending logic for this manual transaction
            // (Assuming metadata has the message details)
            return;
        }

        // 2. Check if it's a Subscription (B2B)
        // Here we would link the user to the subscription and active their API Keys
    }
}
