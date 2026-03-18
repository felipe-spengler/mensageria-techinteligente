@extends('layouts.admin')

@section('title', 'Relatórios de Envios')

@section('content')
    <div class="flex flex-col space-y-8">
        
        <!-- Filter Bar -->
        <div class="glass p-4 rounded-3xl border-dash-700 flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-2 px-4 border-r border-white/5">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            
            <a href="{{ route('admin.logs') }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ !request('status') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'text-gray-500 hover:text-gray-300' }}">Todos</a>
            <a href="{{ route('admin.logs', ['status' => 'sent']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status') === 'sent' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-gray-500 hover:text-gray-300' }}">Sucesso</a>
            <a href="{{ route('admin.logs', ['status' => 'queued']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status') === 'queued' ? 'bg-gray-700 text-white' : 'text-gray-500 hover:text-gray-300' }}">Na Fila</a>
            <a href="{{ route('admin.logs', ['status' => 'failed']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status') === 'failed' ? 'bg-red-600 text-white shadow-lg shadow-red-900/20' : 'text-gray-500 hover:text-gray-300' }}">Erro</a>
        </div>

        <!-- Logs Table -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-dash-900/50 border-b border-white/5">
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Destinatário</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Mensagem</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Usuário/Plano</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest text-center">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($logs as $log)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.022-.014-.022-.014-.022-.014-.422-.211-2.078-1.022-2.394-1.139-.316-.117-.547-.117-.781.234s-.902 1.139-1.102 1.348-.398.234-.781.023c-.383-.211-1.613-.594-3.078-1.906-1.144-1.022-1.921-2.285-2.148-2.671-.227-.386-.023-.594.168-.785.176-.176.383-.445.574-.668.191-.223.258-.383.383-.641s.062-.485-.023-.668c-.086-.183-.781-1.883-1.07-2.574-.289-.691-.58-.597-.781-.597h-.668c-.234 0-.613.086-.933.434s-1.211 1.184-1.211 2.883 1.234 3.328 1.406 3.563c.172.234 2.426 3.707 5.871 5.203.82.355 1.457.566 1.953.723.824.262 1.57.227 2.164.137.66-.1 2.039-.832 2.328-1.64.289-.809.289-1.504.203-1.643-.082-.132-.293-.211-.676-.421z"/></svg>
                                            </div>
                                            <span class="text-sm font-bold text-white tabular-nums">{{ $log->to }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs text-gray-400 max-w-xs truncate">{{ $log->message }}</p>
                                    </td>
                                    <td class="px-8 py-6">
                                        @if($log->apiKey)
                                            <p class="text-[10px] font-bold text-white">{{ $log->apiKey->user->name }}</p>
                                            <p class="text-[8px] text-blue-400 uppercase tracking-tighter">{{ $log->apiKey->plan->name }}</p>
                                        @else
                                            <span class="text-[10px] font-bold text-gray-600 italic">Envio Manual</span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-6">
                                        @php
                                            $colors = [
                                                'sent' => 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
                                                'queued' => 'bg-gray-500/10 border-gray-500/20 text-gray-400',
                                                'failed' => 'bg-red-500/10 border-red-500/20 text-red-400',
                                            ];
                                            $labels = ['sent' => 'Sucesso', 'queued' => 'Fila', 'failed' => 'Erro'];
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wide {{ $colors[$log->status] ?? $colors['queued'] }}">
                                            {{ $labels[$log->status] ?? $log->status }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-sm text-center font-bold text-gray-500 tabular-nums">
                                        {{ $log->created_at->format('d/m H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-8 border-t border-white/5">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="p-20 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-dash-900 border border-white/5 rounded-[32px] flex items-center justify-center mb-6 text-gray-700">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-2">Sem registros</h4>
                    <p class="text-sm text-gray-500 max-w-sm">Nenhum envio foi processado com os filtros atuais.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
