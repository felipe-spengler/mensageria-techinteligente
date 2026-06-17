<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class LoginController extends Controller
{
    use FormatsPhoneNumber;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('admin');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function showResetRequestForm()
    {
        return view('auth.passwords.phone');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Formata e limpa o número para encontrar no banco
        $phoneDigits = preg_replace('/\D/', '', $request->phone);
        
        // Tenta achar um usuário cujo telefone contenha esses dígitos
        // Como o telefone pode estar salvo com ou sem DDI (55) ou formatação, fazemos uma busca flexível
        $user = User::where('phone', 'like', '%' . $phoneDigits . '%')
            ->orWhereRaw("replace(replace(replace(replace(phone, ' ', ''), '-', ''), '(', ''), ')', '') like ?", ['%' . $phoneDigits . '%'])
            ->first();

        if (!$user) {
            // Se o número digitado for ex: 45999144796, podemos também tentar buscar retirando o nono dígito
            if (strlen($phoneDigits) === 11 && $phoneDigits[2] === '9') {
                $withoutNine = substr($phoneDigits, 0, 2) . substr($phoneDigits, 3);
                $user = User::where('phone', 'like', '%' . $withoutNine . '%')
                    ->orWhereRaw("replace(replace(replace(replace(phone, ' ', ''), '-', ''), '(', ''), ')', '') like ?", ['%' . $withoutNine . '%'])
                    ->first();
            }
        }

        if (!$user) {
            return back()->withErrors([
                'phone' => 'Nenhum usuário encontrado com o telefone informado.',
            ])->onlyInput('phone');
        }

        // Gera código de 6 dígitos
        $code = mt_rand(100000, 999999);
        
        // Salva no cache por 10 minutos
        Cache::put('password_recovery_code_' . $phoneDigits, [
            'user_id' => $user->id,
            'code' => $code,
        ], now()->addMinutes(10));

        // Envia mensagem via WhatsApp usando a instância do Admin (User 1)
        try {
            $formattedPhone = $this->formatBrazilianNumber($phoneDigits);
            if ($formattedPhone) {
                $adminInstance = \App\Models\WhatsappInstance::where('user_id', 1)->first();
                $session = $adminInstance ? $adminInstance->session_name : null;

                if ($session) {
                    $redis = Redis::connection();
                    $message = "🔐 *Recuperação de Senha*\n\nSeu código de verificação é: *{$code}*\n\nInsira este código na página de recuperação para redefinir sua senha. Este código é válido por 10 minutos.";
                    
                    $redis->rpush('wpp_messages:' . $session, json_encode([
                        'to' => $formattedPhone,
                        'message' => $message,
                        'is_system_notification' => true,
                        'session' => $session
                    ]));
                } else {
                    Log::error('Erro ao enviar código de recuperação: Instância de WhatsApp do Admin não configurada.');
                }
            } else {
                Log::error('Erro ao enviar código de recuperação: Telefone inválido para formatação: ' . $phoneDigits);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enfileirar recuperação de senha: ' . $e->getMessage());
        }

        // Redireciona com o telefone na query string
        return redirect()->route('password.verify', ['phone' => $phoneDigits])
            ->with('status', 'Código enviado com sucesso via WhatsApp!');
    }

    public function showVerifyCodeForm(Request $request)
    {
        $phone = $request->query('phone');
        return view('auth.passwords.reset', compact('phone'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $phoneDigits = preg_replace('/\D/', '', $request->phone);
        $cachedData = Cache::get('password_recovery_code_' . $phoneDigits);

        if (!$cachedData || $cachedData['code'] != $request->code) {
            return back()->withErrors([
                'code' => 'Código de verificação inválido ou expirado.',
            ])->onlyInput('code');
        }

        $user = User::find($cachedData['user_id']);
        if (!$user) {
            return back()->withErrors([
                'code' => 'Usuário inválido.',
            ]);
        }

        // Atualiza a senha
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Limpa o código do cache
        Cache::forget('password_recovery_code_' . $phoneDigits);

        return redirect()->route('login')->with('status', 'Sua senha foi redefinida com sucesso!');
    }
}
