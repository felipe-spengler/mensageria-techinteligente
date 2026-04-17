<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiKey;

class CheckActivePlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Admins sempre passam. Se não logado, o middleware 'auth' já cuida.
        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        // Verifica se existe QUALQUER chave ativa para este usuário
        $hasActivePlan = ApiKey::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (!$hasActivePlan) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Plano ativo necessário para usar este recurso.'], 403);
            }
            
            // Se estiver tentando acessar o dashboard em si, permitimos para ele ver o aviso de pagamento.
            // O próprio dashboard tem sua lógica interna.
            if ($request->routeIs('admin.dashboard')) {
                return $next($request);
            }

            return redirect()->route('admin.dashboard')->with('error', 'Assine um plano para liberar o acesso total ao sistema.');
        }

        return $next($request);
    }
}
