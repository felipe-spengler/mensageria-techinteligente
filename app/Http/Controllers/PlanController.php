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
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'password' => 'required|min:6',
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        $plan = \App\Models\Plan::find($request->plan_id);
        $txid = 'PLAN-' . strtoupper(\Illuminate\Support\Str::random(8));

        $transaction = \App\Models\PixTransaction::create([
            'txid' => $txid,
            'amount' => $plan->price,
            'status' => 'pending',
            'metadata' => json_encode([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'type' => 'subscription'
            ]),
        ]);

        return response()->json([
            'success' => true,
            'txid' => $txid,
            'qr_code' => 'Pague R$ ' . $plan->price . ' - Chave PIX: sua-chave'
        ]);
    }
}
