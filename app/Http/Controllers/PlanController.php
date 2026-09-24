<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = \App\Models\Plan::where('is_active', true)->get();
        return view('plans', compact('plans'));
    }

    public function purchase($id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        return view('purchase', compact('plan'));
    }

    public function processPurchase(Request $request)
    {
        try {
            $isLoggedIn = auth()->check();

            $rules = [
                'plan_id' => 'required|exists:plans,id',
            ];

            if (!$isLoggedIn) {
                $rules += [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'phone' => 'required|string',
                    'password' => 'required|min:6',
                ];
            }

            $request->validate($rules);

            if ($isLoggedIn) {
                $user = auth()->user();
            } else {
                $user = \App\Models\User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                ]);
            }

            $plan = \App\Models\Plan::find($request->plan_id);
            $txid = 'PLAN-' . strtoupper(\Illuminate\Support\Str::random(8));

            // Tenta criar cobrança PIX no Mercado Pago
            $mpAccessToken = \App\Helpers\SettingsHelper::get('mp_access_token');
            $mpEnabled = \App\Helpers\SettingsHelper::get('mp_enabled', 'false') === 'true';
            
            $pixPayload = null;
            $qrCodeImage = null;

            if ($mpEnabled && $mpAccessToken) {
                try {
                    $payload = [
                        "transaction_amount" => (float)$plan->price,
                        "payment_method_id" => "pix",
                        "description" => 'Plano ' . $plan->name,
                        "external_reference" => $txid,
                        "notification_url" => url('/api/v1/webhook/mercadopago'),
                        "payer" => [
                            "email" => $user->email,
                            "first_name" => $user->name,
                            "identification" => ["type" => "CPF", "number" => "11111111111"], // Preenchimento obrigatório genérico caso o MP exija
                        ]
                    ];

                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . $mpAccessToken,
                        'X-Idempotency-Key' => uniqid(),
                    ])->post("https://api.mercadopago.com/v1/payments", $payload);

                    if ($response->successful()) {
                        $payment = $response->json();
                        
                        $pixPayload = $payment['point_of_interaction']['transaction_data']['qr_code'] ?? null;
                        $qrCodeImage = $payment['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Mercado Pago Payment Error: ' . $response->body());
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erro ao criar cobrança Mercado Pago: ' . $e->getMessage());
                }
            }

            $transaction = \App\Models\PixTransaction::create([
                'user_id' => $user->id,
                'txid' => $txid,
                'amount' => $plan->price,
                'status' => 'pending',
                'payload' => $pixPayload,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'type' => 'subscription'
                ],
            ]);

            return response()->json([
                'success' => true,
                'txid' => $txid,
                'pix_payload' => $pixPayload,
                'qr_code_image' => $qrCodeImage,
                'qr_code' => $pixPayload ?? 'Aguardando configuração do Mercado Pago'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro no processPurchase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ocorreu um erro interno: ' . $e->getMessage()
            ], 500);
        }
    }
}
