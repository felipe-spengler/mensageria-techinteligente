@extends('layouts.admin')

@section('title', 'Progresso de Usuários')

@section('content')
    <div class="flex flex-col space-y-8">

        <!-- Header Info -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="text-2xl font-bold text-white tracking-tight">Progresso & Planos</h3>
                <p class="text-sm text-gray-400 mt-1">Acompanhe o consumo de mensagens e validade dos planos de cada cliente.</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-dash-800 border border-white/5 px-4 py-2 rounded-2xl">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total Usuários</p>
                    <p class="text-lg font-bold text-white">{{ count($usersProgress) }}</p>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-dash-900/50 border-b border-white/5">
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Usuário</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Plano / Status</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Progresso de Uso</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Vencimento</th>
                            <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($usersProgress as $item)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 font-bold">
                                            {{ strtoupper(substr($item['user']->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white">{{ $item['user']->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $item['user']->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-bold text-white">{{ $item['plan_name'] }}</span>
                                        @if($item['is_active'])
                                            <span class="inline-flex items-center text-[9px] font-bold text-emerald-400 uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> Ativo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-[9px] font-bold text-red-400 uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span> Inativo
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="w-full max-w-[200px]">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-[10px] font-bold text-gray-400">{{ $item['usage'] }} / {{ $item['limit'] }}</span>
                                            <span class="text-[10px] font-bold text-blue-400">{{ $item['percent'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-dash-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $item['percent'] > 90 ? 'bg-red-500' : ($item['percent'] > 70 ? 'bg-amber-500' : 'bg-blue-500') }}" 
                                                 style="width: {{ $item['percent'] }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    @if($item['expires_at'])
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-white">{{ $item['expires_at']->format('d/m/Y') }}</span>
                                            <span class="text-[10px] {{ $item['expires_at']->isPast() ? 'text-red-400' : 'text-gray-500' }}">
                                                {{ $item['expires_at']->diffForHumans() }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-600">N/A</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.logs', ['user_id' => $item['user']->id]) }}" class="p-2 bg-dash-800 border border-white/5 rounded-xl hover:bg-dash-700 transition text-gray-400 hover:text-white" title="Ver Logs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </a>
                                        <a href="{{ route('admin.db_manager', ['table' => 'users', 'id' => $item['user']->id]) }}" class="p-2 bg-dash-800 border border-white/5 rounded-xl hover:bg-dash-700 transition text-gray-400 hover:text-white" title="Editar Usuário">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 00-2 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($usersProgress) == 0)
                <div class="p-20 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-dash-900 border border-white/5 rounded-[32px] flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-2">Nenhum Usuário</h4>
                    <p class="text-sm text-gray-500 max-w-sm">Os usuários registrados no sistema aparecerão aqui com seus consumos.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
