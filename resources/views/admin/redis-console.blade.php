@extends('layouts.admin')

@section('title', 'Redis Console')

@section('content')
<div class="flex flex-col space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Redis Console</h1>
            <p class="text-xs text-gray-500 mt-1">Monitoramento e diagnóstico das filas em tempo real</p>
        </div>
        <button onclick="window.location.reload()" class="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 text-xs font-bold rounded-xl transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Atualizar
        </button>
    </div>

    {{-- Queue Overview --}}
    <div class="glass rounded-[32px] border-dash-700 p-6">
        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-4">📊 Filas Ativas (wpp_messages:*)</p>
        @if(count($queues) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($queues as $session => $info)
                <div class="bg-dash-900 rounded-2xl p-4 border border-white/5 hover:border-white/10 transition">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $info['size'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-gray-600' }}"></span>
                            <span class="text-sm font-bold font-mono text-blue-400">{{ $session }}</span>
                        </div>
                        <span class="text-2xl font-black {{ $info['size'] > 100 ? 'text-red-400' : ($info['size'] > 0 ? 'text-amber-400' : 'text-gray-600') }}">
                            {{ number_format($info['size']) }}
                        </span>
                    </div>
                    @if($info['next_to'])
                    <div class="border-t border-white/5 pt-3 space-y-1">
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest">Próxima:</p>
                        <p class="text-xs font-mono text-gray-300">📱 {{ $info['next_to'] }}</p>
                        <p class="text-[10px] text-gray-600 truncate">{{ $info['next_msg'] }}...</p>
                    </div>
                    @else
                    <p class="text-[10px] text-gray-600 border-t border-white/5 pt-3">Fila vazia ✅</p>
                    @endif
                    <div class="flex gap-2 mt-3">
                        <button onclick="setCommand('LRANGE {{ $info['key'] }} 0 9')" class="text-[9px] px-2 py-1 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 rounded-lg transition font-bold">Ver 10 primeiras</button>
                        <button onclick="setCommand('LLEN {{ $info['key'] }}')" class="text-[9px] px-2 py-1 bg-white/5 hover:bg-white/10 text-gray-400 rounded-lg transition font-bold">LLEN</button>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-8">Nenhuma fila encontrada.</p>
        @endif
    </div>

    {{-- Dedup Cache --}}
    @if($dedupKeys->count() > 0)
    <div class="glass rounded-[32px] border-dash-700 p-6">
        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-2">🔑 Cache de Deduplicação Ativo ({{ $dedupKeys->count() }}+ chaves)</p>
        <p class="text-[10px] text-gray-600 mb-4">Mensagens bloqueadas de reenvio nas últimas 24h</p>
        <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto">
            @foreach($dedupKeys->take(30) as $dk)
            <span class="text-[9px] font-mono bg-purple-500/10 text-purple-400 border border-purple-500/20 rounded-lg px-2 py-1">{{ $dk }}</span>
            @endforeach
            @if($dedupKeys->count() > 30)
            <span class="text-[9px] text-gray-500 self-center">... e mais {{ $dedupKeys->count() - 30 }} chaves</span>
            @endif
        </div>
    </div>
    @endif

    {{-- Command Console --}}
    <div class="glass rounded-[32px] border-dash-700 p-6">
        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-4">⌨️ Console de Comandos</p>

        {{-- Recipes --}}
        <div class="mb-4">
            <p class="text-[9px] text-gray-600 uppercase tracking-widest mb-2">Receitas rápidas:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($recipes as $recipe)
                    @php
                        $colorMap = ['blue'=>'blue-500','amber'=>'amber-500','purple'=>'purple-500','green'=>'emerald-500','gray'=>'gray-500'];
                        $color = $colorMap[$recipe['color']] ?? 'gray-500';
                    @endphp
                <button onclick="setCommand('{{ addslashes($recipe['command']) }}')"
                    class="text-[10px] font-bold px-3 py-1.5 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-gray-300 transition whitespace-nowrap">
                    {{ $recipe['label'] }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Command Input --}}
        <form action="{{ route('admin.redis.run') }}" method="POST" class="flex gap-3 mb-4">
            @csrf
            <div class="flex-1 relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-500 font-mono font-bold text-sm">$</span>
                <input type="text" id="redisCommand" name="command"
                    value="{{ session('redis_last_command', old('command', '')) }}"
                    placeholder="LLEN wpp_messages:client_3"
                    autocomplete="off" spellcheck="false"
                    class="w-full bg-black/50 border border-green-500/30 focus:border-green-500/70 text-green-400 font-mono text-sm rounded-2xl pl-10 pr-4 py-3 focus:outline-none transition" />
            </div>
            <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-2xl transition">
                Executar
            </button>
        </form>

        <p class="text-[9px] text-gray-600 mb-4">
            ✅ Permitidos: KEYS, LLEN, LRANGE, GET, TTL, TYPE, EXISTS, HGETALL, SMEMBERS, DBSIZE, INFO, PING &nbsp;|&nbsp;
            ⛔ Bloqueados: FLUSHALL, FLUSHDB, DEL, SET, CONFIG, SHUTDOWN...
        </p>

        {{-- Result --}}
        @php
            $result = session('redis_result') ?? $commandResult ?? null;
            $error  = session('redis_error')  ?? $commandError  ?? null;
        @endphp

        @if($error)
        <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4">
            <p class="text-[9px] font-bold text-red-400 uppercase tracking-widest mb-1">Erro</p>
            <pre class="text-xs text-red-300 font-mono whitespace-pre-wrap">{{ $error }}</pre>
        </div>
        @elseif($result !== null)
        <div class="bg-green-500/5 border border-green-500/20 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[9px] font-bold text-green-400 uppercase tracking-widest">Resultado</p>
                <span class="text-[9px] text-gray-600 font-mono">{{ session('redis_last_command', $lastCommand) }}</span>
            </div>
            <pre class="text-xs text-green-300 font-mono whitespace-pre-wrap max-h-96 overflow-y-auto">{{ $result }}</pre>
        </div>
        @endif
    </div>

    {{-- Worker Status --}}
    @if(count($workerStatus) > 0)
    <div class="glass rounded-[32px] border-dash-700 p-6">
        <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-4">⚙️ Status do Worker</p>
        <div class="space-y-2">
            @foreach($workerStatus as $key => $value)
            <div class="flex items-center justify-between bg-dash-900 rounded-xl px-4 py-2 border border-white/5">
                <span class="text-xs font-mono text-gray-400">{{ $key }}</span>
                <span class="text-xs font-mono text-amber-400">{{ $value ?? 'nil' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
function setCommand(cmd) {
    document.getElementById('redisCommand').value = cmd;
    document.getElementById('redisCommand').focus();
}
</script>
@endsection
