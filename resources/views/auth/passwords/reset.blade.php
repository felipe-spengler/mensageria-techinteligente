<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#0f172a] flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8 bg-[#1e293b] rounded-2xl shadow-2xl border border-slate-700/50">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-white tracking-tight">Redefinir Senha</h2>
            <p class="text-slate-400 mt-2">Insira o código enviado por WhatsApp e escolha uma nova senha</p>
        </div>

        @if (session('status'))
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl text-green-400 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <input type="hidden" name="phone" value="{{ $phone }}">

            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">WhatsApp Enviado</label>
                <div class="px-4 py-3 bg-[#0f172a]/55 border border-slate-700/50 rounded-xl text-slate-300 font-semibold select-none">
                    {{ $phone }}
                </div>
            </div>

            <div>
                <label for="code" class="block text-sm font-medium text-slate-300 mb-2">Código de Verificação (6 dígitos)</label>
                <input type="text" name="code" id="code" required max="999999" pattern="[0-9]{6}"
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-white tracking-[0.5em] text-center font-bold text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-600"
                    placeholder="000000"
                    value="{{ old('code') }}">
                @error('code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Nova Senha</label>
                <input type="password" name="password" id="password" required 
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-500"
                    placeholder="Mínimo 6 caracteres">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-2">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required 
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-500"
                    placeholder="Repita a nova senha">
            </div>

            <button type="submit" 
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-blue-500/20 active:scale-[0.98]">
                Redefinir Senha
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-blue-400 hover:text-blue-300 transition">
                Voltar para o login
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-700 text-center">
            <p class="text-slate-500 text-sm">TechInteligente © 2026</p>
        </div>
    </div>
</body>
</html>
