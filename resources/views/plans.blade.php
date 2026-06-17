<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos Exclusivos - TechInteligente API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(16px); }
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

    <nav class="relative z-30 p-8 flex justify-between items-center max-w-7xl mx-auto">
        <div class="text-2xl font-black tracking-tighter flex items-center gap-2">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-black">T</div>
                <span>TechInteligente</span>
            </a>
        </div>
        
        <!-- Desktop Menu -->
        <div class="flex gap-8 text-sm font-medium text-gray-400 items-center">
            <a href="#planos" class="hover:text-white transition">Preços</a>
            <a href="{{ route('docs') }}" class="hover:text-white transition">Documentação</a>
            @auth
                <a href="/admin" class="bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-blue-500 transition shadow-lg shadow-blue-500/20">Meu Painel</a>
            @else
                <a href="/admin/login" class="bg-white/10 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-white/20 transition">Login</a>
            @endauth
        </div>
    </nav>

    <main class="relative z-10 max-w-7xl mx-auto px-6 pt-12 pb-40">
        <header class="text-center mb-16">
            <div class="inline-block px-4 py-1.5 mb-6 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold tracking-widest uppercase">
                🚀 A API de WhatsApp mais barata do mercado
            </div>
            <h1 class="text-5xl md:text-6xl font-black mb-6 leading-tight">
                Simples, Transparente.<br/>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-purple-400 to-pink-500">Escolha o plano ideal.</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto font-light leading-relaxed">
                Selecione o tipo de envio abaixo e compare os planos. Custo reduzido de verdade para alavancar seu negócio.
            </p>
        </header>

        <!-- Seletor Dinâmico de Tipo de Plano -->
        <div class="flex justify-center mb-16 relative z-20">
            <div class="bg-gray-950 p-1.5 rounded-2xl border border-gray-800/80 flex gap-1 shadow-2xl">
                <button id="btn-text" onclick="setPlanType('text')" class="px-6 py-2.5 rounded-xl text-xs font-black transition-all duration-300 bg-blue-600 text-white shadow-lg shadow-blue-500/20">
                    Apenas Texto
                </button>
                <button id="btn-media" onclick="setPlanType('media')" class="px-6 py-2.5 rounded-xl text-xs font-black transition-all duration-300 text-gray-400 hover:text-white">
                    Texto + Mídia
                </button>
            </div>
        </div>

        @php
            $tiers = [
                ['name' => 'Starter', 'limit' => 200, 'savings' => ''],
                ['name' => 'Premium', 'limit' => 500, 'savings' => '40% OFF'],
                ['name' => 'Business', 'limit' => 1200, 'savings' => '68% OFF'],
                ['name' => 'Enterprise', 'limit' => 1800, 'savings' => '75% OFF'],
            ];
        @endphp

        <div id="planos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 scroll-mt-24">
            @foreach($tiers as $index => $tier)
                @php
                    $textPlan = $plans->filter(fn($p) => str_starts_with($p->name, $tier['name']) && $p->type === 'text')->first();
                    $mediaPlan = $plans->filter(fn($p) => str_starts_with($p->name, $tier['name']) && $p->type === 'media')->first();
                @endphp

                <div class="group relative {{ $index == 1 ? 'lg:scale-105 z-10' : '' }}">
                    @if($index == 1)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 popular-badge px-6 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest z-20 shadow-xl shadow-blue-500/20">Mais Vendido</div>
                    @endif
                    
                    <div class="glass {{ $index == 1 ? 'border-2 border-blue-500/50 bg-blue-500/5' : 'border border-gray-800/60' }} rounded-[2.5rem] p-8 h-full flex flex-col transition-all duration-500 hover:border-gray-600/80">
                        <div class="mb-6">
                            <h3 class="text-2xl font-black mb-1 text-white">{{ $tier['name'] }}</h3>
                            <div class="flex items-center gap-2">
                                <span class="bg-white/5 px-2 py-0.5 rounded text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ number_format($tier['limit'], 0) }} envios/mês</span>
                                @if($tier['savings'])
                                    <span class="text-[10px] font-black text-green-400 uppercase tracking-widest">{{ $tier['savings'] }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Opção: Só Texto -->
                        <div class="plan-details-text transition-all duration-300 flex flex-col flex-1">
                            <div class="mb-8">
                                <div class="text-4xl font-black text-white">R$ {{ number_format($textPlan->price ?? 0, 0) }}</div>
                                <div class="text-[11px] text-gray-400 mt-1">R$ {{ number_format(($textPlan->price ?? 0) / $tier['limit'], 2, ',', '.') }} por mensagem</div>
                            </div>
                            
                            <a href="/purchase/{{ $textPlan->id ?? '#' }}" class="block w-full py-3.5 rounded-2xl text-center text-xs font-black bg-blue-600 hover:bg-blue-500 text-white transition-all duration-300 shadow-lg shadow-blue-500/25">ASSINAR AGORA</a>
                        </div>

                        <!-- Opção: Com Mídia -->
                        <div class="plan-details-media hidden transition-all duration-300 flex flex-col flex-1">
                            <div class="mb-8">
                                <div class="text-4xl font-black text-white">R$ {{ number_format($mediaPlan->price ?? 0, 0) }}</div>
                                <div class="text-[11px] text-gray-400 mt-1">R$ {{ number_format(($mediaPlan->price ?? 0) / $tier['limit'], 2, ',', '.') }} por mensagem</div>
                            </div>
                            
                            <a href="/purchase/{{ $mediaPlan->id ?? '#' }}" class="block w-full py-3.5 rounded-2xl text-center text-xs font-black bg-purple-600 hover:bg-purple-500 text-white transition-all duration-300 shadow-lg shadow-purple-600/25">ASSINAR AGORA</a>
                        </div>

                        <ul class="mt-8 space-y-3.5 flex-1 px-1 border-t border-white/5 pt-6">
                            <li class="flex items-center gap-3 text-xs text-gray-400">
                                <svg class="w-4.5 h-4.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Setup instantâneo via API
                            </li>
                            <li class="flex items-center gap-3 text-xs text-gray-400">
                                <svg class="w-4.5 h-4.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Webhooks de status real
                            </li>
                            <li class="flex items-center gap-3 text-xs text-gray-400">
                                <svg class="w-4.5 h-4.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Dashboard de consumo completo
                            </li>
                            <li class="plan-feature-media hidden flex items-center gap-3 text-xs text-gray-400">
                                <svg class="w-4.5 h-4.5 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Imagens, PDFs e arquivos via API
                            </li>
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </main>

    <footer class="relative z-10 py-12 border-t border-gray-900/60 text-center text-gray-600 text-xs">
        <p>&copy; 2026 TechInteligente - Sua Mensageria Escalonável</p>
    </footer>

    <script>
        function setPlanType(type) {
            const btnText = document.getElementById('btn-text');
            const btnMedia = document.getElementById('btn-media');
            
            const detailsText = document.querySelectorAll('.plan-details-text');
            const detailsMedia = document.querySelectorAll('.plan-details-media');
            const featuresMedia = document.querySelectorAll('.plan-feature-media');
            
            if (type === 'text') {
                btnText.className = "px-6 py-2.5 rounded-xl text-xs font-black transition-all duration-300 bg-blue-600 text-white shadow-lg shadow-blue-500/20";
                btnMedia.className = "px-6 py-2.5 rounded-xl text-xs font-black transition-all duration-300 text-gray-400 hover:text-white";
                
                detailsText.forEach(el => el.classList.remove('hidden'));
                detailsMedia.forEach(el => el.classList.add('hidden'));
                featuresMedia.forEach(el => el.classList.add('hidden'));
            } else {
                btnMedia.className = "px-6 py-2.5 rounded-xl text-xs font-black transition-all duration-300 bg-purple-600 text-white shadow-lg shadow-purple-500/20";
                btnText.className = "px-6 py-2.5 rounded-xl text-xs font-black transition-all duration-300 text-gray-400 hover:text-white";
                
                detailsText.forEach(el => el.classList.add('hidden'));
                detailsMedia.forEach(el => el.classList.remove('hidden'));
                featuresMedia.forEach(el => el.classList.remove('hidden'));
            }
        }
    </script>
</body>
</html>
