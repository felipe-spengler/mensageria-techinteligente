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

    <nav class="relative z-30 p-8 flex justify-between items-center max-w-7xl mx-auto" x-data="{ open: false }">
        <div class="text-2xl font-black tracking-tighter flex items-center gap-2">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">T</div>
                <span>TechInteligente</span>
            </a>
        </div>
        
        <!-- Desktop Menu -->
        <div class="hidden md:flex gap-8 text-sm font-medium text-gray-400">
            <a href="/enviar" class="hover:text-white transition">Envio Particular</a>
            <a href="#planos" class="hover:text-white transition">Preços</a>
            <a href="#github" class="hover:text-white transition">Open Source</a>
            @auth
                <a href="/admin" class="text-blue-400 font-bold hover:text-blue-300 transition">Meu Painel</a>
            @else
                <a href="/admin/login" class="hover:text-white transition">Login</a>
            @endauth
        </div>

        <div class="flex items-center gap-4">
            <a href="#planos" class="hidden sm:block bg-white text-black px-6 py-2 rounded-full text-sm font-bold hover:bg-blue-500 hover:text-white transition">Get Started</a>
            
            <!-- Mobile Menu Toggle -->
            <button onclick="toggleMobileMenu()" class="md:hidden text-gray-400 hover:text-white p-2">
                <svg id="menuIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobileMenu" class="fixed inset-0 bg-[#030712]/95 backdrop-blur-xl z-50 hidden flex-col items-center justify-center gap-8 text-2xl font-black">
            <button onclick="toggleMobileMenu()" class="absolute top-8 right-8 text-gray-400 hover:text-white">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <a href="/enviar" onclick="toggleMobileMenu()" class="hover:text-blue-500 transition">Envio Particular</a>
            <a href="#planos" onclick="toggleMobileMenu()" class="hover:text-blue-500 transition">Preços</a>
            <a href="/admin/login" onclick="toggleMobileMenu()" class="hover:text-blue-500 transition">Login</a>
            <a href="#planos" onclick="toggleMobileMenu()" class="bg-blue-600 text-white px-8 py-3 rounded-full">Começar Agora</a>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('flex');
            document.body.classList.toggle('overflow-hidden');
        }
    </script>

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
            $tiers = [
                ['name' => 'Starter', 'limit' => 200, 'text_id' => 1, 'media_id' => 4, 'savings' => ''],
                ['name' => 'Premium', 'limit' => 500, 'text_id' => 2, 'media_id' => 5, 'savings' => '40% OFF'],
                ['name' => 'Business', 'limit' => 1200, 'text_id' => 3, 'media_id' => 6, 'savings' => '68% OFF'],
            ];

            // Map database results to the tiers
            $planMap = $plans->keyBy('id');
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($tiers as $index => $tier)
                @php
                    $textPlan = $plans->filter(fn($p) => str_starts_with($p->name, $tier['name']) && $p->type === 'text')->first();
                    $mediaPlan = $plans->filter(fn($p) => str_starts_with($p->name, $tier['name']) && $p->type === 'media')->first();
                @endphp

                <div class="group relative {{ $index == 1 ? 'animate-float' : '' }}">
                    @if($index == 1)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 popular-badge px-6 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest z-20 shadow-xl shadow-blue-500/20">Mais Vendido</div>
                    @endif
                    
                    <div class="glass {{ $index == 1 ? 'border-2 border-blue-500/50 bg-blue-500/5' : 'border border-gray-800' }} rounded-[2.5rem] p-8 h-full flex flex-col transition-all duration-500 hover:border-gray-600">
                        <div class="mb-6">
                            <h3 class="text-2xl font-black mb-1 text-white">{{ $tier['name'] }}</h3>
                            <div class="flex items-center gap-2">
                                <span class="bg-white/10 px-2 py-0.5 rounded text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ number_format($tier['limit'], 0) }} Mensagens/mês</span>
                                @if($tier['savings'])
                                    <span class="text-[10px] font-black text-green-400 uppercase tracking-widest">{{ $tier['savings'] }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Opção: Só Texto -->
                        <div class="mb-8 p-5 rounded-3xl bg-white/5 border border-white/5 hover:bg-white/10 transition group/item relative">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-xs font-black text-blue-400 uppercase tracking-widest mb-1">Apenas Texto</h4>
                                    <p class="text-[10px] text-gray-500 italic">Ideal para avisos e notificações simples.</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-black">R$ {{ number_format($textPlan->price ?? 0, 0) }}</div>
                                    <div class="text-[10px] text-gray-400">R$ {{ number_format(($textPlan->price ?? 0) / $tier['limit'], 2, ',', '.') }}/msg</div>
                                </div>
                            </div>
                            <a href="/purchase/{{ $textPlan->id ?? '#' }}" class="block w-full py-3 rounded-xl text-center text-xs font-black bg-white/5 hover:bg-blue-600 transition-all duration-300 border border-white/10 hover:border-blue-500">ASSINAR SÓ TEXTO</a>
                        </div>

                        <!-- Opção: Com Mídia -->
                        <div class="p-5 rounded-3xl bg-gradient-to-br from-purple-600/10 to-transparent border border-purple-500/20 hover:border-purple-500/50 transition shadow-2xl shadow-purple-900/10 relative">
                             <div class="absolute -top-2 right-4 bg-purple-600 px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest">Premium Choice</div>
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-xs font-black text-purple-400 uppercase tracking-widest mb-1">Texto + Mídia</h4>
                                    <p class="text-[10px] text-gray-500 italic">Arquivos, Imagens e PDFs via API.</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-black text-white">R$ {{ number_format($mediaPlan->price ?? 0, 0) }}</div>
                                    <div class="text-[10px] text-gray-400">R$ {{ number_format(($mediaPlan->price ?? 0) / $tier['limit'], 2, ',', '.') }}/msg</div>
                                </div>
                            </div>
                            <a href="/purchase/{{ $mediaPlan->id ?? '#' }}" class="block w-full py-3 rounded-xl text-center text-xs font-black bg-purple-600 hover:bg-purple-500 transition-all duration-300 shadow-lg shadow-purple-600/20">ASSINAR COM MÍDIA</a>
                        </div>

                        <ul class="mt-10 space-y-3 flex-1 px-2">
                            <li class="flex items-center gap-3 text-[11px] text-gray-400">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Setup instantâneo via API
                            </li>
                            <li class="flex items-center gap-3 text-[11px] text-gray-400">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Webhooks de status em tempo real
                            </li>
                            <li class="flex items-center gap-3 text-[11px] text-gray-400">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Dashboard de consumo completo
                            </li>
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </main>

    <footer class="relative z-10 py-12 border-t border-gray-900 text-center text-gray-600 text-xs">
        <p>&copy; 2026 TechInteligente - Sua Mensageria Escalonável</p>
    </footer>
</body>
</html>
