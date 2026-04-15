<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\MessageLog;
use App\Models\PixTransaction;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Dashboard Home
     */
    public function index()
    {
        $user = Auth::user();
        
        $stats = [
            'total_sent' => MessageLog::when(!$user->isAdmin(), fn($q) => $q->whereHas('apiKey', fn($aq) => $aq->where('user_id', $user->id)))
                ->where('status', 'sent')
                ->count(),
                
            'total_queued' => MessageLog::when(!$user->isAdmin(), fn($q) => $q->whereHas('apiKey', fn($aq) => $aq->where('user_id', $user->id)))
                ->where('status', 'queued')
                ->count(),
                
            'total_failed' => MessageLog::when(!$user->isAdmin(), fn($q) => $q->whereHas('apiKey', fn($aq) => $aq->where('user_id', $user->id)))
                ->where('status', 'failed')
                ->count(),
                
            'api_keys_active' => ApiKey::when(!$user->isAdmin(), fn($q) => $q->where('user_id', $user->id))
                ->where('status', 'active')
                ->count(),
        ];

        return view('admin.index', compact('stats'));
    }

    /**
     * API Keys & Payments Page
     */
    public function apiKeys()
    {
        $user = Auth::user();
        $keys = ApiKey::when(!$user->isAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->with(['user', 'plan'])
            ->latest()
            ->get();

        return view('admin.api-keys', compact('keys'));
    }

    /**
     * Message Reports Page
     */
    public function logs(Request $request)
    {
        $user = Auth::user();
        $query = MessageLog::query()->with('apiKey.user')
            ->when(!$user->isAdmin(), function($q) use ($user) {
                $q->whereHas('apiKey', fn($aq) => $aq->where('user_id', $user->id));
            });

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $logs = $query->latest()->paginate(20);

        return view('admin.logs', compact('logs'));
    }

    /**
     * WhatsApp Connection Page
     */
    public function whatsapp()
    {
        return view('admin.whatsapp');
    }

    /**
     * Save Global Asaas Settings
     */
    public function saveAsaas(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'asaas_key' => 'required|string',
            'asaas_mode' => 'required|in:sandbox,production',
        ]);

        Setting::setValue('asaas_api_key', $request->asaas_key, 'asaas');
        Setting::setValue('asaas_mode', $request->asaas_mode, 'asaas');

        return back()->with('success', 'Configurações de Asaas salvas com sucesso!');
    }
    /**
     * Financial Settings Page
     */
    public function financeiro()
    {
        $transactions = PixTransaction::latest()->paginate(20);
        $totalTransactions = PixTransaction::count();
        $paidTransactions = PixTransaction::where('status', 'paid')->count();
        $pendingTransactions = PixTransaction::where('status', 'pending')->count();

        return view('admin.financeiro', compact(
            'transactions',
            'totalTransactions',
            'paidTransactions',
            'pendingTransactions'
        ));
    }

    public function saveFinanceiro(Request $request)
    {
        $request->validate([
            'asaas_key' => 'nullable|string',
            'asaas_mode' => 'required|in:sandbox,production',
            'asaas_webhook_token' => 'nullable|string',
        ]);

        Setting::setValue('asaas_api_key', $request->asaas_key ?? '', 'asaas');
        Setting::setValue('asaas_mode', $request->asaas_mode, 'asaas');
        Setting::setValue('asaas_enabled', $request->has('asaas_enabled') ? 'true' : 'false', 'asaas');
        
        if ($request->asaas_webhook_token) {
            Setting::setValue('asaas_webhook_token', $request->asaas_webhook_token, 'asaas');
        }

        return back()->with('success', 'Configurações financeiras salvas com sucesso!');
    }

    public function testAsaas()
    {
        $key = Setting::getValue('asaas_api_key');
        $mode = Setting::getValue('asaas_mode', 'sandbox');

        if (!$key) {
            return back()->with('error', 'Configure a API Key antes de testar.');
        }

        try {
            $baseUrl = $mode === 'production' 
                ? 'https://www.asaas.com/api/v3' 
                : 'https://sandbox.asaas.com/api/v3';

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'access_token' => $key,
            ])->get("{$baseUrl}/index/stats");

            if ($response->successful()) {
                return back()->with('success', 'Conexão com Asaas estabelecida com sucesso! (Conta Ativa)');
            }

            $error = $response->json()['errors'][0]['description'] ?? 'Erro desconhecido na API do Asaas.';
            return back()->with('error', 'Falha na conexão: ' . $error);

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao processar teste: ' . $e->getMessage());
        }
    }

    /**
     * Plans Management Page
     */
    public function plans()
    {
        $plans = Plan::all();
        return view('admin.plans', compact('plans'));
    }

    public function storePlan(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'price' => 'required|numeric',
            'message_limit' => 'required|integer',
            'description' => 'required|string',
        ]);

        Plan::create($validated);
        return back()->with('success', 'Plano criado com sucesso!');
    }

    public function updatePlan(Request $request, Plan $plan)
    {

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'price' => 'required|numeric',
            'message_limit' => 'required|integer',
            'description' => 'required|string',
        ]);

        $plan->update($validated);
        return back()->with('success', 'Plano atualizado com sucesso!');
    }

    public function destroyPlan(Plan $plan)
    {

        $plan->delete();
        return back()->with('success', 'Plano deletado com sucesso!');
    }

    /**
     * Store new API Key
     */
    public function storeApiKey(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        ApiKey::create([
            'user_id' => Auth::id(),
            'plan_id' => $request->plan_id,
            'key' => 'sk_' . \Illuminate\Support\Str::random(32),
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        return back()->with('success', 'Chave API criada com sucesso!');
    }

    public function destroyApiKey(ApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $apiKey->delete();
        return back()->with('success', 'Chave deletada com sucesso!');
    }
}
