<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#0f172a] flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8 bg-[#1e293b] rounded-2xl shadow-2xl border border-slate-700/50">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-white tracking-tight">Bem-vindo</h2>
            <p class="text-slate-400 mt-2">Acesse sua conta administrativa</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300 mb-2">E-mail</label>
                <input type="email" name="email" id="email" required 
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-500"
                    placeholder="exemplo@email.com">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Senha</label>
                <input type="password" name="password" id="password" required 
                    class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder-slate-500"
                    placeholder="••••••••">
            </div>

            <button type="submit" 
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-blue-500/20 active:scale-[0.98]">
                Entrar no Painel
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-700 text-center">
            <p class="text-slate-500 text-sm">TechInteligente © 2026</p>
        </div>
    </div>
</body>
</html>
