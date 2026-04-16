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

        // Security: Check Webhook Token if configured
        $storedToken = \App\Helpers\SettingsHelper::get('asaas_webhook_token');
        if ($storedToken && $request->header('asaas-access-token') !== $storedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

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
        $transaction = PixTransaction::where('txid', $payment['externalReference'] ?? '')->first();
        if (!$transaction) return;

        $transaction->update(['status' => 'paid']);
        $metadata = json_encode($transaction->metadata); // Assuming it might be casted or needs decoding
        if (is_string($metadata)) $metadata = json_decode($metadata, true);
        else $metadata = (array)$transaction->metadata;

        // 1. Check if it's a manual PIX (B2C) - Handled above with status update

        // 2. Check if it's a Subscription/Upgrade (SaaS)
        if (isset($metadata['type']) && $metadata['type'] === 'subscription') {
            $userId = $metadata['user_id'];
            $planId = $metadata['plan_id'];
            $plan = \App\Models\Plan::find($planId);

            if ($plan) {
                $apiKey = \App\Models\ApiKey::firstOrNew(['user_id' => $userId]);
                
                // Only generate a new key if it's the first time
                if (!$apiKey->exists) {
                    $apiKey->key = \Illuminate\Support\Str::random(40);
                }

                $apiKey->fill([
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'expires_at' => now()->addDays($plan->duration_days),
                    'name' => 'Chave Principal - ' . $plan->name
                ])->save();
                
                Log::info("Plan activated/renewed for user {$userId}: {$plan->name}. Key preserved: " . ($apiKey->wasRecentlyCreated ? 'No (New)' : 'Yes'));
            }
        }
    }
}
