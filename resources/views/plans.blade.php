<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos e Preços - Mensageria TechInteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); }
        .gradient-border { 
            position: relative;
            background: #111827;
            border-radius: 1.5rem;
        }
        .gradient-border::before {
            content: "";
            position: absolute;
            inset: -1px;
            background: linear-gradient(to bottom right, #3b82f6, #8b5cf6, #ec4899);
            border-radius: 1.5rem;
            z-index: -1;
            opacity: 0.5;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="p-6 flex justify-between items-center max-w-7xl mx-auto">
        <div class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-500">
            TechInteligente
        </div>
        <a href="/admin/login" class="text-sm font-medium hover:text-blue-400 transition">Login Admin</a>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-20 text-center">
        <h1 class="text-5xl font-extrabold mb-4">Escolha seu plano de disparos</h1>
        <p class="text-gray-400 mb-16 max-w-2xl mx-auto">Conecte sua API do WhatsApp em segundos e comece a escalar suas comunicações de forma profissional.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($plans as $plan)
            <div class="{{ $loop->index == 1 ? 'gradient-border' : 'glass border border-gray-800 rounded-3xl' }} p-8 flex flex-col justify-between transition hover:scale-105 duration-300">
                <div>
                    <h2 class="text-xl font-bold mb-2">{{ $plan->name }}</h2>
                    <div class="text-4xl font-bold mb-4">R$ {{ number_format($plan->price, 2, ',', '.') }}<span class="text-sm text-gray-500 font-normal">/mês</span></div>
                    <p class="text-gray-400 text-sm mb-6">{{ $plan->description }}</p>
                    
                    <ul class="text-left space-y-4 mb-8">
                        <li class="flex items-center text-sm text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ $plan->message_limit > 900000 ? 'Mensagens Ilimitadas' : number_format($plan->message_limit, 0, ',', '.') . ' mensagens' }}
                        </li>
                        <li class="flex items-center text-sm text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Duração: {{ $plan->duration_days }} dias
                        </li>
                        <li class="flex items-center text-sm text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Painel de Controle Full
                        </li>
                        <li class="flex items-center text-sm text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Integração via API
                        </li>
                    </ul>
                </div>

                <a href="/purchase/{{ $plan->id }}" class="block w-full py-4 rounded-2xl font-bold transition {{ $loop->index == 1 ? 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500' : 'bg-gray-800 hover:bg-gray-700' }}">
                    Começar Agora
                </a>
            </div>
            @endforeach
        </div>
    </main>
</body>
</html>
