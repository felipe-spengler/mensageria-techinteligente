@extends('layouts.admin')

@section('title', 'Financeiro')

@section('content')
    <div class="flex flex-col space-y-10">

        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="text-2xl font-bold text-white tracking-tight">Configurações Financeiras</h3>
                <p class="text-sm text-gray-400 mt-1">Gerencie a integração com o gateway de pagamento e visualize as transações PIX.</p>
            </div>
            @if(session('success'))
                <div class="flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-2xl text-xs font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center space-x-2 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-2 rounded-2xl text-xs font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <!-- Status Asaas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="glass rounded-3xl p-6 border-dash-700">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Gateway</p>
                <div class="flex items-center space-x-3">
                    @php $asaasKey = \App\Models\Setting::getValue('asaas_api_key'); @endphp
                    @if($asaasKey)
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-sm font-bold text-emerald-400">Asaas Configurado</span>
                    @else
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="text-sm font-bold text-red-400">Não Configurado</span>
                    @endif
                </div>
            </div>
            <div class="glass rounded-3xl p-6 border-dash-700">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Integração</p>
                @php $enabled = \App\Models\Setting::getValue('asaas_enabled', 'false') === 'true'; @endphp
                <div class="flex items-center space-x-3">
                    @if($enabled)
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-sm font-bold text-emerald-400">Ativa (Recebendo)</span>
                    @else
                        <span class="w-3 h-3 rounded-full bg-gray-500"></span>
                        <span class="text-sm font-bold text-gray-500">Inativa (Pausa)</span>
                    @endif
                </div>
            </div>
            <div class="glass rounded-3xl p-6 border-dash-700">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Ambiente</p>
                @php $mode = \App\Models\Setting::getValue('asaas_mode', 'sandbox'); @endphp
                <div class="flex items-center space-x-3">
                    @if($mode === 'production')
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-sm font-bold text-blue-400">Produção</span>
                    @else
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-sm font-bold text-amber-400">Sandbox (Testes)</span>
                    @endif
                </div>
            </div>
            <div class="glass rounded-3xl p-6 border-dash-700">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Transações PIX</p>
                <p class="text-2xl font-bold text-white">{{ $totalTransactions }}</p>
                <p class="text-[10px] text-gray-500 mt-1">{{ $paidTransactions }} pagas / {{ $pendingTransactions }} pendentes</p>
            </div>
        </div>

        <!-- Asaas Config Card -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            <div class="px-10 py-8 border-b border-white/5">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-bold text-white">Integração Asaas</h4>
                        <p class="text-xs text-gray-500">Configure sua API Key para receber pagamentos via PIX</p>
                    </div>
                    <form action="{{ route('admin.financeiro.test') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center space-x-2 bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 px-4 py-2 rounded-xl text-[10px] font-bold hover:bg-indigo-600/20 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span>Testar Conexão</span>
                        </button>
                    </form>
                </div>
            </div>
            <form action="{{ route('admin.financeiro.save') }}" method="POST" class="p-10 space-y-8">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">API Key do Asaas</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="asaas_key" 
                                value="{{ \App\Models\Setting::getValue('asaas_api_key') }}"
                                class="w-full bg-[#0a0a0c] border border-white/5 rounded-2xl p-4 pr-12 text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-mono text-sm"
                                placeholder="$aact_YourKeyHere...">
                            <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-600 mt-2">Encontre em: <span class="text-indigo-400">Asaas → Configurações → Integrações</span></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Ambiente de Operação</label>
                        <div class="space-y-3">
                            <label class="flex items-center space-x-4 p-4 rounded-2xl border cursor-pointer transition-all {{ $mode === 'sandbox' ? 'border-amber-500/40 bg-amber-500/5' : 'border-white/5 hover:border-white/10' }}">
                                <input type="radio" name="asaas_mode" value="sandbox" {{ $mode === 'sandbox' ? 'checked' : '' }} class="accent-amber-500">
                                <div>
                                    <p class="text-sm font-bold text-white">Sandbox</p>
                                    <p class="text-[10px] text-gray-500">Para testes sem cobrança real</p>
                                </div>
                            </label>
                            <label class="flex items-center space-x-4 p-4 rounded-2xl border cursor-pointer transition-all {{ $mode === 'production' ? 'border-blue-500/40 bg-blue-500/5' : 'border-white/5 hover:border-white/10' }}">
                                <input type="radio" name="asaas_mode" value="production" {{ $mode === 'production' ? 'checked' : '' }} class="accent-blue-500">
                                <div>
                                    <p class="text-sm font-bold text-white">Produção</p>
                                    <p class="text-[10px] text-gray-500">Cobranças reais em dinheiro</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Tokens e Segurança</label>
                        <div class="space-y-4">
                            <div x-data="{ showToken: false }">
                                <p class="text-[10px] text-gray-500 mb-2">Webhook Access Token (Opcional)</p>
                                <div class="relative">
                                    <input :type="showToken ? 'text' : 'password'" name="asaas_webhook_token" 
                                        value="{{ \App\Models\Setting::getValue('asaas_webhook_token') }}"
                                        class="w-full bg-[#0a0a0c] border border-white/5 rounded-2xl p-4 pr-12 text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-mono text-sm"
                                        placeholder="Seu token de segurança do webhook">
                                    <button type="button" @click="showToken = !showToken" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition">
                                        <svg x-show="!showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <svg x-show="showToken" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 mb-2">Sua Webhook URL (Copie para o Asaas)</p>
                                <div class="bg-dash-900 border border-white/5 rounded-2xl p-4 flex items-center justify-between">
                                    <code class="text-xs text-indigo-400 font-mono">{{ url('/api/v1/webhook') }}</code>
                                    <button type="button" @click="navigator.clipboard.writeText('{{ url('/api/v1/webhook') }}'); alert('URL Copiada!')" class="text-gray-500 hover:text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012-2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Opções Adicionais</label>
                        <div class="glass p-6 rounded-3xl border-dash-700 bg-emerald-500/5">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div>
                                    <p class="text-sm font-bold text-white">Habilitar Pagamentos</p>
                                    <p class="text-[10px] text-gray-500">Se desativado, o checkout via PIX será suspenso.</p>
                                </div>
                                <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out bg-gray-800 rounded-full">
                                    <input type="checkbox" name="asaas_enabled" value="1" {{ $enabled ? 'checked' : '' }} class="opacity-0 w-0 h-0 peer">
                                    <span class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-all peer-checked:translate-x-6 peer-checked:bg-emerald-500"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>


                <div class="pt-4 border-t border-white/5 flex items-center justify-between">
                    <p class="text-[10px] text-gray-600 max-w-sm">A chave é criptografada no banco de dados. Nunca compartilhe com terceiros.</p>
                    <button type="submit" class="btn-grad px-8 py-3 rounded-2xl font-bold text-sm shadow-xl shadow-blue-900/20">Salvar Configurações</button>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-white">Histórico de Transações PIX</h4>
                        <p class="text-xs text-gray-500">Últimas cobranças geradas no sistema</p>
                    </div>
                </div>
            </div>

            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-dash-900/50 border-b border-white/5">
                                <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">TXID</th>
                                <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Valor</th>
                                <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Tipo</th>
                                <th class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($transactions as $tx)
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-8 py-5 font-mono text-xs text-gray-400">{{ $tx->txid }}</td>
                                    <td class="px-8 py-5 text-sm font-bold text-white">R$ {{ number_format($tx->amount, 2, ',', '.') }}</td>
                                    <td class="px-8 py-5">
                                        @if($tx->status === 'paid')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase">Pago</span>
                                        @elseif($tx->status === 'pending')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold uppercase">Pendente</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-bold uppercase">{{ $tx->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-5 text-xs text-gray-500 capitalize">{{ $tx->metadata['type'] ?? 'manual' }}</td>
                                    <td class="px-8 py-5 text-xs text-gray-500 font-mono">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-10 py-6 border-t border-white/5">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="p-20 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-dash-900 border border-white/5 rounded-[32px] flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-2">Nenhuma Transação</h4>
                    <p class="text-sm text-gray-500 max-w-sm">As cobranças PIX geradas pelo sistema aparecerão aqui.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
