@extends('layouts.admin')

@section('title', 'Simulador de API')

@section('content')
<div class="max-w-4xl mx-auto" x-data="apiTester()">
    <div class="text-center mb-12">
        <h3 class="text-3xl font-bold text-white mb-2 tracking-tight">API Sandbox</h3>
        <p class="text-gray-400 text-sm">Teste as requisições do sistema simulando uma integração externa em tempo real.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Form -->
        <div class="glass p-8 rounded-[32px] border-dash-700 shadow-2xl space-y-6">
            <div class="space-y-4">
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1 mb-2 block">Sua API Key</label>
                    <input type="text" x-model="apiKey" placeholder="sk_live_..." class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-blue-400 focus:outline-none focus:border-blue-500/50 transition-all font-mono">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1 mb-2 block">Número de Destino</label>
                    <input type="text" x-model="to" placeholder="45999998888" class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-gray-300 focus:outline-none focus:border-blue-500/50 transition-all">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1 mb-2 block">Mensagem</label>
                    <textarea x-model="message" rows="4" placeholder="Olá! Este é um teste da API." class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-gray-300 focus:outline-none focus:border-blue-500/50 transition-all resize-none"></textarea>
                </div>

                <div x-data="{ showMedia: false }">
                    <button @click="showMedia = !showMedia" class="text-[10px] text-gray-500 hover:text-blue-400 transition-colors flex items-center mb-2">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Adicionar Mídia (Opcional)</span>
                    </button>
                    <input x-show="showMedia" type="text" x-model="media" placeholder="https://link-da-imagem.jpg" class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-gray-300 focus:outline-none focus:border-blue-500/50 transition-all">
                </div>
            </div>

            <button @click="sendTest" :disabled="loading" class="w-full py-4 bg-blue-600 hover:bg-blue-700 rounded-2xl text-xs font-bold text-white uppercase tracking-widest transition-all shadow-lg shadow-blue-900/40 flex items-center justify-center space-x-3">
                <template x-if="loading">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </template>
                <span x-text="loading ? 'Enviando...' : 'Executar Request'"></span>
            </button>
        </div>

        <!-- Result -->
        <div class="flex flex-col space-y-6">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest ml-1">Resposta do Servidor</h4>
            <div class="flex-1 glass rounded-[32px] border-dash-700 p-8 shadow-2xl relative overflow-hidden flex flex-col">
                <div x-show="!response" class="flex-1 flex flex-col items-center justify-center text-center space-y-4 opacity-30">
                    <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-4m0 0l4 4m-4-4V4"></path></svg>
                    <p class="text-xs font-medium">Aguardando execução do teste...</p>
                </div>

                <div x-show="response" class="flex-1 text-xs font-mono leading-relaxed overflow-auto">
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-2 py-1 rounded text-[10px] font-bold" :class="status >= 400 ? 'bg-red-500/20 text-red-400' : 'bg-emerald-500/20 text-emerald-400'" x-text="'HTTP ' + status"></span>
                        <span class="text-[10px] text-gray-500" x-text="time + 'ms'"></span>
                    </div>
                    <pre class="text-blue-300" x-text="JSON.stringify(response, null, 2)"></pre>
                </div>
            </div>
            
            <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-2xl">
                <p class="text-[10px] text-amber-500 leading-relaxed italic"><strong>Nota:</strong> Este simulador faz uma requisição POST real para {{ url('/api/v1/send') }}. As mensagens enviadas aqui serão contabilizadas no seu limite de plano.</p>
            </div>
        </div>
    </div>
</div>

<script>
function apiTester() {
    return {
        apiKey: '{{ auth()->user()->apiKeys()->where('status', 'active')->first()?->key }}',
        to: '',
        message: '',
        media: '',
        loading: false,
        response: null,
        status: null,
        time: 0,

        async sendTest() {
            if (!this.apiKey || !this.to || !this.message) {
                alert('Preencha a API Key, Destino e Mensagem.');
                return;
            }

            this.loading = true;
            this.response = null;
            const start = Date.now();

            try {
                const res = await fetch('/api/v1/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + this.apiKey
                    },
                    body: JSON.stringify({
                        to: this.to,
                        message: this.message,
                        media: this.media
                    })
                });

                this.status = res.status;
                this.response = await res.json();
                this.time = Date.now() - start;
            } catch (err) {
                this.status = 500;
                this.response = { error: 'Falha na requisição ao servidor. Verifique sua conexão.' };
            }

            this.loading = false;
        }
    }
}
</script>
@endsection
