@extends('layouts.admin')

@section('title', 'Pagamentos & API')

@section('content')
    <div class="flex flex-col space-y-12" x-data="{ asaasModal: false, docsModal: false }">
        
        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-white tracking-tight">Suas Chaves e Planos</h3>
                <p class="text-sm text-gray-400">Gerencie suas credenciais de acesso e assinatura.</p>
            </div>
            <div class="flex items-center space-x-4">
                @if(auth()->user()->isAdmin())
                    <button @click="asaasModal = true" class="flex items-center space-x-2 bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 px-6 py-3 rounded-2xl text-xs font-bold hover:bg-indigo-600/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Configurar Asaas Global</span>
                    </button>
                @endif
                <button @click="docsModal = true" class="flex items-center space-x-2 bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 px-6 py-3 rounded-2xl text-xs font-bold hover:bg-emerald-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Como usar a API</span>
                </button>
            </div>
        </div>

        <!-- Keys Table Card -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            @if($keys->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-dash-900/50 border-b border-white/5">
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Plano/Usuário</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">API Key (Secreta)</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest text-center">Expiração</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($keys as $key)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-10 h-10 rounded-xl bg-dash-800 flex items-center justify-center border border-white/10 group-hover:border-blue-500/30 transition-colors">
                                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-white">{{ $key->plan->name }}</p>
                                                <p class="text-[10px] text-gray-400 capitalize">{{ $key->user->name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center space-x-3 bg-dash-950/50 border border-white/5 rounded-xl px-4 py-2 w-fit group-hover:border-blue-500/20 transition-all">
                                            <code class="text-xs text-blue-400 font-mono tracking-wider tabular-nums">{{ substr($key->key, 0, 8) }}****************</code>
                                            <button @click="navigator.clipboard.writeText('{{ $key->key }}'); alert('Copiado!')" class="text-gray-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-sm">
                                        @if($key->status === 'active')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase tracking-wide">Ativa</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-wide">Inativa</span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-6 text-sm text-center font-bold text-gray-500 font-mono tabular-nums">
                                        {{ $key->expires_at ? $key->expires_at->format('d/m/Y') : 'Ilimitado' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-20 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-dash-900 border border-white/5 rounded-[32px] flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.691.346a6 6 0 01-3.86.517l-2.388-.477a2 2 0 00-1.022.547l-1.168 1.168a2 2 0 00.556 3.212 9.035 9.035 0 008.22 0 2 2 0 00.556-3.212l-1.168-1.168zM12 9V4m0 0L9 7m3-3l3 3"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-2">Sem Chaves Ativas</h4>
                    <p class="text-sm text-gray-500 mb-8 max-w-sm">Você ainda não possui nenhum plano ativo ou chave de API gerada. Escolha um plano para começar.</p>
                    <a href="/enviar" class="btn-grad px-8 py-4 rounded-3xl text-sm font-bold shadow-lg shadow-blue-900/10 transition">Página de Planos</a>
                </div>
            @endif
        </div>
    </div>

    <!-- Asaas Modal -->
    <div x-show="asaasModal" class="fixed inset-0 z-[60] flex items-center justify-center p-6 bg-dash-950/80 backdrop-blur-sm" x-cloak>
        <div class="glass w-full max-w-lg rounded-[40px] p-10 border-dash-700 shadow-3xl" @click.away="asaasModal = false">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-bold text-white">Configuração Asaas Master</h3>
                <button @click="asaasModal = false" class="text-gray-500 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg></button>
            </div>
            
            <form action="{{ route('admin.asaas.save') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Asaas API Key (Produção ou Sandbox)</label>
                    <input type="password" name="asaas_key" value="{{ \App\Models\Setting::getValue('asaas_key') }}" required class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Ambiente</label>
                    <select name="asaas_mode" class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none appearance-none transition-all">
                        <option value="sandbox" {{ \App\Models\Setting::getValue('asaas_mode') === 'sandbox' ? 'selected' : '' }}>Sandbox (Testes)</option>
                        <option value="production" {{ \App\Models\Setting::getValue('asaas_mode') === 'production' ? 'selected' : '' }}>Produção (Real)</option>
                    </select>
                </div>
                <button type="submit" class="w-full btn-grad py-5 rounded-3xl font-bold text-sm shadow-xl shadow-blue-900/30">Salvar Configurações</button>
            </form>
        </div>
    </div>

    <!-- API Docs Modal -->
    <div x-show="docsModal" class="fixed inset-0 z-[60] flex items-center justify-center p-6 bg-dash-950/80 backdrop-blur-sm" x-cloak>
        <div class="glass w-full max-w-2xl rounded-[40px] p-10 border-dash-700 shadow-3xl" @click.away="docsModal = false">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-bold text-white leading-tight">Documentação da API</h3>
                <button @click="docsModal = false" class="text-gray-500 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg></button>
            </div>
            
            @include('components.api-docs-modal')
        </div>
    </div>
@endsection
