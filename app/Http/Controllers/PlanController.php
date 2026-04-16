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

            // Tenta criar cobrança PIX no Asaas
            $asaasApiKey = db_setting('asaas_api_key');
            $asaasEnabled = db_setting('asaas_enabled', 'false') === 'true';
            $asaasMode = db_setting('asaas_mode', 'sandbox');
            
            $pixPayload = null;
            $qrCodeImage = null;

            if ($asaasEnabled && $asaasApiKey) {
                try {
                    $baseUrl = $asaasMode === 'production' 
                        ? 'https://www.asaas.com/api/v3' 
                        : 'https://sandbox.asaas.com/api/v3';

                    $customerId = $this->getOrCreateAsaasCustomer($user, $asaasApiKey, $baseUrl);
                    
                    if ($customerId) {
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'access_token' => $asaasApiKey,
                            'Content-Type' => 'application/json',
                        ])->post("{$baseUrl}/payments", [
                            'customer' => $customerId,
                            'billingType' => 'PIX',
                            'value' => $plan->price,
                            'dueDate' => now()->addDay()->format('Y-m-d'),
                            'description' => 'Plano ' . $plan->name,
                            'externalReference' => $txid,
                        ]);

                        if ($response->successful()) {
                            $payment = $response->json();
                            
                            $qrResponse = \Illuminate\Support\Facades\Http::withHeaders([
                                'access_token' => $asaasApiKey,
                            ])->get("{$baseUrl}/payments/{$payment['id']}/pixQrCode");

                            if ($qrResponse->successful()) {
                                $qrData = $qrResponse->json();
                                $pixPayload = $qrData['payload'] ?? null;
                                $qrCodeImage = $qrData['encodedImage'] ?? null;
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning('Asaas Payment Error: ' . $response->body());
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Asaas Customer creation failed for user: ' . $user->email);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erro ao criar cobrança Asaas: ' . $e->getMessage());
                }
            }

            $transaction = \App\Models\PixTransaction::create([
                'user_id' => $user->id,
                'txid' => $txid,
                'amount' => $plan->price,
                'status' => 'pending',
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
                'qr_code' => $pixPayload ?? 'Aguardando configuração do Asaas'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro no processPurchase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ocorreu um erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getOrCreateAsaasCustomer($user, $asaasApiKey, $baseUrl)
    {
        try {
            // 1. Busca cliente existente por email
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'access_token' => $asaasApiKey,
            ])->get("{$baseUrl}/customers", [
                'email' => $user->email,
            ]);

            if ($response->successful()) {
                $customers = $response->json();
                if (!empty($customers['data'])) {
                    return $customers['data'][0]['id'];
                }
            }

            // 2. Prepara CPF/CNPJ (Asaas exige 11 ou 14 dígitos)
            $rawPhone = preg_replace('/[^0-9]/', '', $user->phone);
            // Se o telefone tiver 11 dígitos, usamos como CPF "fake" (temporário)
            // Se não, deixamos em branco e deixamos o Asaas reclamar ou usamos um placeholder
            $cpfCnpj = (strlen($rawPhone) === 11 || strlen($rawPhone) === 14) ? $rawPhone : null;

            // 3. Cria novo cliente
            $payload = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            if ($cpfCnpj) {
                $payload['cpfCnpj'] = $cpfCnpj;
            }

            $createResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'access_token' => $asaasApiKey,
                'Content-Type' => 'application/json',
            ])->post("{$baseUrl}/customers", $payload);

            if ($createResponse->successful()) {
                return $createResponse->json()['id'];
            }

            \Illuminate\Support\Facades\Log::warning('Asaas Customer creation failed: ' . $createResponse->body());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Asaas getOrCreateAsaasCustomer exception: ' . $e->getMessage());
        }

        return null;
    }
}
