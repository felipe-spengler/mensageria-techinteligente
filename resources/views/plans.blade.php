<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos Exclusivos - TechInteligente API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/framer-motion@10.16.4/dist/framer-motion.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(12px); }
        .selection-panel { position: relative; }
        .selection-panel::before {
            content: ""; position: absolute; inset: -1px;
            background: linear-gradient(45deg, #3b82f6, #8b5cf6, #ec4899);
            border-radius: 2rem; z-index: -1; opacity: 0.3;
        }
        .popular-badge {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="bg-[#030712] text-white min-h-screen selection:bg-blue-500/30">
    <!-- Efeito de fundo -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/10 rounded-full blur-[120px]"></div>
    </div>

    <nav class="relative z-10 p-8 flex justify-between items-center max-w-7xl mx-auto">
        <div class="text-2xl font-black tracking-tighter flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">T</div>
            <span>TechInteligente</span>
        </div>
        <div class="hidden md:flex gap-8 text-sm font-medium text-gray-400">
            <a href="#" class="hover:text-white transition">Como Funciona</a>
            <a href="#" class="hover:text-white transition">API Docs</a>
            <a href="/admin/login" class="hover:text-white transition">Acessar Painel</a>
        </div>
        <a href="#planos" class="bg-white text-black px-6 py-2 rounded-full text-sm font-bold hover:bg-blue-500 hover:text-white transition">Contratar</a>
    </nav>

    <main class="relative z-10 max-w-7xl mx-auto px-6 pt-20 pb-40">
        <header class="text-center mb-24">
            <div class="inline-block px-4 py-1.5 mb-6 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold tracking-widest uppercase">
                🚀 A API de WhatsApp mais barata do mercado
            </div>
            <h1 class="text-6xl md:text-7xl font-black mb-6 leading-tight">
                Escala é poder.<br/>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-purple-400 to-pink-500">Pague menos por mensagem.</span>
            </h1>
            <p class="text-gray-400 text-xl max-w-3xl mx-auto font-light leading-relaxed">
                Quanto maior o volume, maior o desconto. Compare o custo unitário e veja por que somos o melhor parceiro para o seu crescimento.
            </p>
        </header>

        @php
            $textPlans = $plans->where('type', 'text')->sortBy('price');
            $mediaPlans = $plans->where('type', 'media')->sortBy('price');
        @endphp

        <!-- Seção: Só Texto -->
        <section id="planos" class="mb-32">
            <div class="flex items-center gap-4 mb-12">
                <div class="h-px flex-1 bg-gray-800"></div>
                <h2 class="text-3xl font-bold text-blue-400">Categoria: Envios de Texto</h2>
                <div class="h-px flex-1 bg-gray-800"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($textPlans as $index => $plan)
                <div class="group relative {{ $index == 2 ? 'animate-float' : '' }}">
                    @if($index == 1)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 popular-badge px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter z-20">Mais Popular</div>
                    @elseif($index == 2)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-pink-600 px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter z-20">Melhor Valor</div>
                    @endif
                    
                    <div class="glass {{ $index == 2 ? 'border-2 border-pink-500/50' : 'border border-gray-800' }} rounded-[2rem] p-8 h-full flex flex-col transition-all duration-500 hover:bg-white/5">
                        <h3 class="text-xl font-bold mb-1 text-gray-300">{{ $plan->name }}</h3>
                        <p class="text-xs text-gray-500 mb-6 font-medium">Até {{ number_format($plan->message_limit, 0) }} envios/mês</p>
                        
                        <div class="mb-8">
                            <span class="text-5xl font-black">R$ {{ number_format($plan->price, 0) }}</span>
                            <span class="text-gray-500 text-sm">/mês</span>
                        </div>

                        <div class="bg-white/5 rounded-2xl p-4 mb-8 border border-white/10">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-400">Custo por mensagem</span>
                                <span class="text-sm font-bold text-green-400">R$ {{ number_format($plan->price / $plan->message_limit, 2, ',', '.') }}</span>
                            </div>
                            @if($index > 0)
                                <div class="text-[10px] text-blue-400 font-bold uppercase tracking-tight">
                                    📉 {{ $index == 1 ? '40% mais barato que Starter' : '68% mais barato que Starter' }}
                                </div>
                            @endif
                        </div>

                        <ul class="space-y-4 mb-10 flex-1">
                            <li class="flex items-center gap-3 text-sm text-gray-400">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                API Key Instantânea
                            </li>
                            <li class="flex items-center gap-3 text-sm text-gray-400">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Relatórios em Tempo Real
                            </li>
                            <li class="flex items-center gap-3 text-sm text-gray-400">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Suporte via E-mail
                            </li>
                        </ul>

                        <a href="/purchase/{{ $plan->id }}" class="w-full py-4 rounded-xl text-center font-bold text-sm transition-all duration-300 {{ $index == 1 ? 'bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/20' : ($index == 2 ? 'bg-pink-600 hover:bg-pink-500 shadow-lg shadow-pink-600/20' : 'bg-gray-800 hover:bg-gray-700') }}">
                            Assinar Agora
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Seção: Com Mídia -->
        <section>
            <div class="flex items-center gap-4 mb-12">
                <div class="h-px flex-1 bg-gray-800"></div>
                <h2 class="text-3xl font-bold text-purple-400">Categoria: Envios + Mídia (Imagens/PDF)</h2>
                <div class="h-px flex-1 bg-gray-800"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($mediaPlans as $index => $plan)
                <div class="group relative">
                    @if($index == 2)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-emerald-600 px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter z-20">Recomendado Corporativo</div>
                    @endif
                    
                    <div class="glass border border-gray-800 rounded-[2rem] p-8 h-full flex flex-col transition-all duration-500 hover:bg-white/5">
                        <h3 class="text-xl font-bold mb-1 text-gray-300">{{ $plan->name }}</h3>
                        <p class="text-xs text-gray-500 mb-6 font-medium">Misture texto, imagens e documentos</p>
                        
                        <div class="mb-8">
                            <span class="text-5xl font-black">R$ {{ number_format($plan->price, 0) }}</span>
                            <span class="text-gray-500 text-sm">/mês</span>
                        </div>

                        <div class="bg-white/5 rounded-2xl p-4 mb-8 border border-white/10">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-400">Custo por envio</span>
                                <span class="text-sm font-bold text-purple-400">R$ {{ number_format($plan->price / $plan->message_limit, 2, ',', '.') }}</span>
                            </div>
                            @if($index > 0)
                                <div class="text-[10px] text-purple-400 font-bold uppercase tracking-tight">
                                    📉 {{ $index == 1 ? '44% de economia' : '67% de economia' }}
                                </div>
                            @endif
                        </div>

                        <ul class="space-y-4 mb-10 flex-1">
                            <li class="flex items-center gap-3 text-sm text-gray-400">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Upload de Arquivos via Base64
                            </li>
                            <li class="flex items-center gap-3 text-sm text-gray-400">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Visualização de Mídia no Log
                            </li>
                            <li class="flex items-center gap-3 text-sm text-gray-400">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Suporte Prioritário 24/7
                            </li>
                        </ul>

                        <a href="/purchase/{{ $plan->id }}" class="w-full py-4 rounded-xl text-center font-bold text-sm bg-purple-600 hover:bg-purple-500 transition-all duration-300 shadow-lg shadow-purple-600/20">
                            Assinar Categoria Mídia
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="relative z-10 py-12 border-t border-gray-900 text-center text-gray-600 text-xs">
        <p>&copy; 2026 TechInteligente - Sua Mensageria Escalonável</p>
    </footer>
</body>
</html>
