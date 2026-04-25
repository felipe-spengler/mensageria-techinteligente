@extends('layouts.admin')

@section('title', 'Relatórios de Envios')

@section('content')
    <div class="flex flex-col space-y-8" x-data="{ 
        timeLeft: {{ $nextSendIn ?? 0 }},
        init() {
            if (this.timeLeft > 0) {
                setInterval(() => { if (this.timeLeft > 0) this.timeLeft-- }, 1000);
            }
        }
    }">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="glass p-6 rounded-[32px] border-dash-700 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Total Hoje</p>
                    <h3 class="text-3xl font-black text-white">{{ $logs->total() }}</h3>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="glass p-6 rounded-[32px] border-dash-700 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Sucesso</p>
                    <h3 class="text-3xl font-black text-white">{{ $logs->where('status', 'sent')->count() }}</h3>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-5 text-emerald-500 group-hover:opacity-10 transition-opacity">
                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="glass p-6 rounded-[32px] border-dash-700 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-[10px] font-bold text-amber-500 uppercase tracking-widest mb-1">Na Fila</p>
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-black text-white">{{ $queuedCount }}</h3>
                        <template x-if="timeLeft > 0">
                            <span class="text-xs font-bold text-amber-500/80 animate-pulse">(em <span x-text="timeLeft"></span>s)</span>
                        </template>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-5 text-amber-500 group-hover:opacity-10 transition-opacity">
                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="glass p-6 rounded-[32px] border-dash-700 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-1">Erros</p>
                    <h3 class="text-3xl font-black text-white">{{ $logs->where('status', 'failed')->count() }}</h3>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-5 text-red-500 group-hover:opacity-10 transition-opacity">
                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="glass p-4 rounded-3xl border-dash-700 flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-2 px-4 border-r border-white/5">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            
            <a href="{{ route('admin.logs') }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ !request('status') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'text-gray-500 hover:text-gray-300' }}">Todos</a>
            <a href="{{ route('admin.logs', ['status' => 'sent']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status') === 'sent' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-gray-500 hover:text-gray-300' }}">Sucesso</a>
            <a href="{{ route('admin.logs', ['status' => 'queued']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status') === 'queued' ? 'bg-amber-600 text-white shadow-lg shadow-amber-900/20' : 'text-gray-500 hover:text-gray-300' }}">Na Fila</a>
            <a href="{{ route('admin.logs', ['status' => 'failed']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status') === 'failed' ? 'bg-red-600 text-white shadow-lg shadow-red-900/20' : 'text-gray-500 hover:text-gray-300' }}">Erro</a>

            @if(auth()->user()->isAdmin())
                <div class="ml-auto flex items-center space-x-4">
                    <button @click="window.location.reload()" class="p-2 text-gray-500 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                    <form action="{{ route('admin.logs.clear') }}" method="POST" onsubmit="return confirm('ATENÇÃO: Isso vai excluir TODAS as mensagens na fila de todos os clientes. Continuar?')">
                        @csrf
                        <button type="submit" class="bg-red-600/10 border border-red-500/20 text-red-500 px-4 py-2 rounded-xl text-[10px] font-bold hover:bg-red-600 hover:text-white transition group flex items-center space-x-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>LIMPAR TODA A FILA</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if($queuedReason)
        <div class="bg-amber-500/10 border border-amber-500/20 p-6 rounded-[32px] flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-amber-500/20 rounded-2xl flex items-center justify-center text-amber-500">
                    <svg class="w-5 h-5 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-white">Estado da Fila</p>
                    <p class="text-xs text-amber-400/80">{{ $queuedReason }}</p>
                </div>
            </div>
            @if($nextSendIn > 0)
            <div class="text-right">
                <span class="text-2xl font-black text-amber-500" x-text="timeLeft"></span>
                <span class="text-[10px] font-bold text-amber-500/50 uppercase tracking-widest ml-1">segundos</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Logs Table -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-dash-900/50 border-b border-white/5">
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Destinatário</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Mensagem</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Instância</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Usuário/Plano</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest text-center">Data</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($logs as $log)
                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-dash-800 flex items-center justify-center text-gray-500 border border-white/5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            </div>
                                            <span class="text-sm font-bold text-gray-300">{{ $log->to }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs text-gray-500 max-w-md line-clamp-2 leading-relaxed" title="{{ $log->message }}">{{ $log->message }}</p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-mono text-blue-400 uppercase tracking-tighter">
                                                {{ $log->instance->session_name ?? 'mensageria-tech' }}
                                            </span>
                                            <span class="text-[8px] text-gray-600 mt-0.5">Sessão WPP</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-300">{{ $log->apiKey->user->name ?? 'N/A' }}</span>
                                            <span class="text-[10px] text-gray-600 uppercase tracking-tighter">{{ $log->apiKey->name ?? 'API' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        @if($log->status === 'sent')
                                            <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 text-[10px] font-bold border border-emerald-500/20 uppercase">Sucesso</span>
                                        @elseif($log->status === 'failed')
                                            <div class="flex flex-col">
                                                <span class="px-3 py-1 rounded-full bg-red-500/10 text-red-500 text-[10px] font-bold border border-red-500/20 uppercase w-fit">Erro</span>
                                                <span class="text-[10px] text-red-400/60 mt-1">{{ $log->error_message }}</span>
                                            </div>
                                        @else
                                            <span class="px-3 py-1 rounded-full bg-gray-500/10 text-gray-500 text-[10px] font-bold border border-white/5 uppercase tracking-widest">Fila</span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-400">{{ $log->created_at->format('d/m') }}</span>
                                            <span class="text-[10px] text-gray-600">{{ $log->created_at->format('H:i') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            @if($log->status === 'failed')
                                                <form action="{{ route('admin.logs.retry', $log->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="p-2 text-amber-500 hover:text-white hover:bg-amber-500/20 rounded-xl transition" title="Tentar Reenviar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <form action="{{ route('admin.logs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Excluir este registro?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-600 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                    <div class="px-8 py-6 border-t border-white/5">
                        {{ $logs->links() }}
                    </div>
                @endif
            @else
                <div class="p-20 text-center">
                    <div class="w-20 h-20 bg-dash-800 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/5 text-gray-600">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 01-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Nenhum registro encontrado</h3>
                    <p class="text-gray-500">Altere os filtros ou aguarde novas requisições.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
