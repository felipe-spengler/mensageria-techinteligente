@extends('layouts.admin')

@section('title', 'Painel Geral')

@section('content')
    @if($pendingPayment)
    <div class="mb-10 glass p-8 rounded-[40px] border border-amber-500/30 bg-amber-500/5 overflow-hidden relative group">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="flex flex-col md:flex-row items-center justify-between relative z-10 gap-6">
            <div class="flex items-center space-x-6">
                <div class="w-16 h-16 bg-amber-500/20 border border-amber-500/30 text-amber-500 rounded-3xl flex items-center justify-center animate-pulse">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white mb-1">Aguardando Pagamento</h3>
                    <p class="text-gray-400 text-sm">Identificamos um pedido pendente ({{ $pendingPayment->txid }}). Conclua o pagamento via PIX para liberar seus recursos.</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                @if($pendingPayment->payload)
                    <button onclick="navigator.clipboard.writeText('{{ $pendingPayment->payload }}'); alert('Código PIX Copiado!')" class="btn-grad px-8 py-4 rounded-3xl text-sm font-bold shadow-lg shadow-blue-900/40 whitespace-nowrap">Copiar Código PIX</button>
                @endif
                <a href="#planos" class="bg-dash-800 hover:bg-dash-700 px-8 py-4 rounded-3xl text-sm font-bold border border-white/5 transition whitespace-nowrap">Ver Planos</a>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Stats Card -->
        <div class="glass p-8 rounded-[40px] border-dash-700 relative overflow-hidden group hover:scale-[1.02] transition-transform">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all"></div>
            <div class="flex items-center justify-between mb-6 relative">
                <div class="w-14 h-14 bg-blue-600/10 border border-blue-600/20 text-blue-500 rounded-3xl flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
            <div class="relative">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Mensagens Enviadas</p>
                <h3 class="text-4xl font-bold text-white mb-2 tabular-nums">{{ $stats['total_sent'] }}</h3>
                <p class="text-[10px] text-emerald-400 font-semibold flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                    <span>Status: Sucesso</span>
                </p>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="glass p-8 rounded-[40px] border-dash-700 relative overflow-hidden group hover:scale-[1.02] transition-transform">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-gray-500/10 rounded-full blur-3xl group-hover:bg-gray-500/20 transition-all"></div>
            <div class="flex items-center justify-between mb-6 relative">
                <div class="w-14 h-14 bg-gray-600/10 border border-gray-600/20 text-gray-500 rounded-3xl flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="relative">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Na Fila</p>
                <h3 class="text-4xl font-bold text-white mb-2 tabular-nums">{{ $stats['total_queued'] }}</h3>
                <p class="text-[10px] {{ $queuedReason ? 'text-amber-400' : 'text-gray-400' }} font-semibold tracking-wide">
                    {{ $queuedReason ?? 'Aguardando processamento' }}
                </p>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="glass p-8 rounded-[40px] border-dash-700 relative overflow-hidden group hover:scale-[1.02] transition-transform">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-500/10 rounded-full blur-3xl group-hover:bg-red-500/20 transition-all"></div>
            <div class="flex items-center justify-between mb-6 relative">
                <div class="w-14 h-14 bg-red-600/10 border border-red-600/20 text-red-500 rounded-3xl flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="relative">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Falhas de Envio</p>
                <h3 class="text-4xl font-bold text-white mb-2 tabular-nums">{{ $stats['total_failed'] }}</h3>
                <p class="text-[10px] text-red-400 font-semibold tracking-wide">Verifique as configurações</p>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="glass p-8 rounded-[40px] border-dash-700 relative overflow-hidden group hover:scale-[1.02] transition-transform">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all"></div>
            <div class="flex items-center justify-between mb-6 relative">
                <div class="w-14 h-14 bg-amber-600/10 border border-amber-600/20 text-amber-500 rounded-3xl flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
            </div>
            <div class="relative">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Planos Ativos</p>
                <h3 class="text-4xl font-bold text-white mb-2 tabular-nums">{{ $stats['api_keys_active'] }}</h3>
                <p class="text-[10px] text-amber-400 font-semibold tracking-wide">Chaves de API em uso</p>
            </div>
        </div>
    </div>
    
    @if(!$hasActiveKey && !auth()->user()->isAdmin())
    <div id="planos" class="mt-12 space-y-8">
        <div class="text-center">
            <h3 class="text-2xl font-bold text-white mb-2">🚀 Comece Agora</h3>
            <p class="text-gray-400 text-sm">Escolha um dos planos abaixo para liberar o envio de mensagens via API.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($plans as $plan)
            <div class="glass p-8 rounded-[40px] border border-white/5 hover:border-blue-500/30 transition-all flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 text-[10px] font-bold uppercase tracking-widest">{{ $plan->type === 'media' ? 'Texto + Mídia' : 'Apenas Texto' }}</span>
                        <div class="text-2xl font-black text-white">R$ {{ number_format($plan->price, 0) }}</div>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-2">{{ $plan->name }}</h4>
                    <p class="text-xs text-gray-500 mb-6 leading-relaxed">{{ $plan->description }}</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center space-x-3 text-[11px] text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>{{ number_format($plan->message_limit, 0) }} mensagens/mês</span>
                        </li>
                        <li class="flex items-center space-x-3 text-[11px] text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>API REST & Webhooks</span>
                        </li>
                    </ul>
                </div>
                <a href="/purchase/{{ $plan->id }}" class="btn-grad w-full py-4 rounded-3xl text-center text-sm font-bold shadow-lg shadow-blue-900/40">Assinar Agora</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Banner Info -->
    <div class="mt-12 group overflow-hidden relative">
        <div class="absolute inset-0 bg-blue-600 rounded-[40px] opacity-10 group-hover:opacity-15 transition-opacity"></div>
        <div class="relative p-12 flex flex-col md:flex-row items-center justify-between border border-blue-500/20 rounded-[40px]">
            <div class="mb-8 md:mb-0">
                <h3 class="text-2xl font-bold text-white mb-2">Seja bem-vindo ao seu painel Mensageria</h3>
                <p class="text-gray-400 text-sm max-w-xl">Gerencie seus envios de WhatsApp, monitore campanhas e configure suas integrações via API de forma simples e rápida.</p>
            </div>
            <div class="flex space-x-4">
                <a href="/admin/api-keys" class="btn-grad px-8 py-4 rounded-3xl text-sm font-bold shadow-lg shadow-blue-900/40">Minhas Configurações</a>
                <a href="/admin/logs" class="bg-dash-800 hover:bg-dash-700 px-8 py-4 rounded-3xl text-sm font-bold border border-white/5 transition">Ver Relatórios</a>
            </div>
        </div>
    </div>
@endsection
