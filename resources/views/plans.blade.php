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
        <p class="text-gray-400 mb-16 max-w-2xl mx-auto">Tabela de preços SaaS para envio de mensagens profissionais via WhatsApp API.</p>

        @php
            $textPlans = $plans->where('type', 'text');
            $mediaPlans = $plans->where('type', 'media');
        @endphp

        <!-- Só Texto -->
        <div class="mb-20">
            <h2 class="text-2xl font-bold mb-8 text-blue-400">Categoria: Só Texto</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                @foreach($textPlans as $plan)
                <div class="glass border border-gray-800 rounded-3xl p-8 flex flex-col justify-between transition hover:border-blue-500/50 duration-300">
                    <div>
                        <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                        <div class="text-4xl font-bold mb-4">R$ {{ number_format($plan->price, 0) }}<span class="text-sm text-gray-500 font-normal">/mês</span></div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                {{ number_format($plan->message_limit, 0) }} Mensagens
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Custo unitário: R$ {{ number_format($plan->price / $plan->message_limit, 2, ',', '.') }}
                            </li>
                        </ul>
                    </div>
                    <a href="/purchase/{{ $plan->id }}" class="block w-full py-3 text-center rounded-xl font-bold bg-gray-800 hover:bg-blue-600 transition">Assinar</a>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Com Mídia -->
        <div>
            <h2 class="text-2xl font-bold mb-8 text-purple-400">Categoria: Com Mídia (Imagens/PDF)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                @foreach($mediaPlans as $plan)
                <div class="glass border border-gray-800 rounded-3xl p-8 flex flex-col justify-between transition hover:border-purple-500/50 duration-300">
                    <div>
                        <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                        <div class="text-4xl font-bold mb-4">R$ {{ number_format($plan->price, 0) }}<span class="text-sm text-gray-500 font-normal">/mês</span></div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center text-sm text-gray-300">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                {{ number_format($plan->message_limit, 0) }} Mensagens + Mídia
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Custo unitário: R$ {{ number_format($plan->price / $plan->message_limit, 2, ',', '.') }}
                            </li>
                        </ul>
                    </div>
                    <a href="/purchase/{{ $plan->id }}" class="block w-full py-3 text-center rounded-xl font-bold bg-purple-600/20 text-purple-400 border border-purple-500/30 hover:bg-purple-600 hover:text-white transition">Assinar</a>
                </div>
                @endforeach
            </div>
        </div>
    </main>
</body>
</html>
