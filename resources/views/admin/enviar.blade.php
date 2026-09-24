@extends('layouts.admin')

@section('title', 'Disparo de Mensagens')

@section('content')
<div class="max-w-6xl mx-auto" x-data="massSender()">
    <div class="text-center mb-12">
        <h3 class="text-3xl font-bold text-white mb-2 tracking-tight">Disparo Manual</h3>
        <p class="text-gray-400 text-sm">Envie mensagens em lote ou individuais diretamente pelo painel. (As mensagens entrarão na fila normal de envio)</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Form -->
        <div class="lg:col-span-5 glass p-8 rounded-[32px] border-dash-700 shadow-2xl space-y-6">
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2 ml-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block">Números de Destino</label>
                        <div class="flex items-center space-x-2">
                            <label class="cursor-pointer text-[10px] text-fuchsia-400 hover:text-fuchsia-300 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span>Importar TXT/CSV</span>
                                <input type="file" accept=".txt,.csv" class="hidden" @change="handleFileUpload">
                            </label>
                        </div>
                    </div>
                    <textarea x-model="to" rows="6" placeholder="Digite os números separados por linha ou vírgula..." class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-gray-300 focus:outline-none focus:border-fuchsia-500/50 transition-all resize-none"></textarea>
                    <p class="text-[10px] text-gray-500 mt-2 ml-1">Total: <span x-text="numberCount()" class="font-bold text-fuchsia-400"></span> números válidos</p>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1 mb-2 block">Mensagem</label>
                    <textarea x-model="message" rows="5" placeholder="Olá! Este é um envio da plataforma." class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-gray-300 focus:outline-none focus:border-fuchsia-500/50 transition-all resize-none"></textarea>
                </div>

                <div x-data="{ showMedia: false }">
                    <button @click="showMedia = !showMedia" class="text-[10px] text-gray-500 hover:text-fuchsia-400 transition-colors flex items-center mb-2">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Adicionar Mídia (Link JPG/MP4 - Opcional)</span>
                    </button>
                    <input x-show="showMedia" type="text" x-model="media" placeholder="https://link-da-imagem.jpg" class="w-full bg-black/30 border border-white/5 rounded-2xl p-4 text-sm text-gray-300 focus:outline-none focus:border-fuchsia-500/50 transition-all">
                </div>
            </div>

            <button type="button" @click="startSending()" :disabled="sending || numberCount() === 0 || !message" class="w-full py-4 bg-fuchsia-600 hover:bg-fuchsia-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-2xl text-xs font-bold text-white uppercase tracking-widest transition-all shadow-lg shadow-fuchsia-900/40 flex items-center justify-center space-x-3">
                <template x-if="sending">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </template>
                <span x-text="sending ? 'Processando Lote...' : 'Adicionar à Fila de Envio'"></span>
            </button>
        </div>

        <!-- Result -->
        <div class="lg:col-span-7 flex flex-col space-y-6">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest ml-1">Progresso do Envio</h4>
            <div class="flex-1 glass rounded-[32px] border-dash-700 p-8 shadow-2xl relative overflow-hidden flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex space-x-4">
                        <div class="text-center">
                            <span class="block text-2xl font-bold text-white" x-text="results.length">0</span>
                            <span class="text-[10px] text-gray-500 uppercase tracking-widest">Processados</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-2xl font-bold text-emerald-400" x-text="successCount()">0</span>
                            <span class="text-[10px] text-gray-500 uppercase tracking-widest">Sucesso</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-2xl font-bold text-red-400" x-text="errorCount()">0</span>
                            <span class="text-[10px] text-gray-500 uppercase tracking-widest">Falhas</span>
                        </div>
                    </div>
                    
                    <template x-if="sending">
                        <div class="flex items-center space-x-2 text-fuchsia-400">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="text-xs font-bold uppercase tracking-widest" x-text="`${progress}%`"></span>
                        </div>
                    </template>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-dash-800 rounded-full h-1.5 mb-6">
                    <div class="bg-fuchsia-500 h-1.5 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                </div>

                <div x-show="results.length === 0" class="flex-1 flex flex-col items-center justify-center text-center space-y-4 opacity-30 mt-8">
                    <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="text-xs font-medium">Os resultados aparecerão aqui.</p>
                </div>

                <div x-show="results.length > 0" class="flex-1 overflow-auto space-y-3 pr-2">
                    <template x-for="res in results" :key="res.id">
                        <div class="flex items-center justify-between p-3 rounded-xl border border-white/5" :class="res.success ? 'bg-emerald-500/5' : 'bg-red-500/5'">
                            <div class="flex items-center space-x-3">
                                <div class="w-2 h-2 rounded-full" :class="res.success ? 'bg-emerald-500' : 'bg-red-500'"></div>
                                <span class="text-xs text-gray-300 font-mono" x-text="res.number"></span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-1 rounded" :class="res.success ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10'" x-text="res.message"></span>
                        </div>
                    </template>
                </div>
            </div>
            
            <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-2xl flex items-start space-x-3">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-[10px] text-amber-500 leading-relaxed italic">
                    <strong>Importante:</strong> Ao disparar, as mensagens são apenas <strong>colocadas na fila</strong>. O envio real dependerá do status da sua conexão WhatsApp e da velocidade padrão (1 msg a cada 30 segundos) para evitar banimentos. Cada número debitará 1 crédito do seu plano.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function massSender() {
    return {
        apiKey: '{{ auth()->user()->apiKeys()->where("status", "active")->first()?->key }}',
        to: '',
        message: '',
        media: '',
        sending: false,
        results: [],
        progress: 0,

        numberCount() {
            return this.getNumbers().length;
        },

        getNumbers() {
            return this.to.split(/[\n,;]+/)
                .map(n => n.replace(/\D/g, ''))
                .filter(n => n.length >= 10);
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const newNumbers = text.split(/[\n,;]+/)
                    .map(n => n.replace(/\D/g, ''))
                    .filter(n => n.length >= 10);
                
                if (newNumbers.length > 0) {
                    const currentNumbers = this.getNumbers();
                    const allNumbers = [...new Set([...currentNumbers, ...newNumbers])];
                    this.to = allNumbers.join('\n');
                }
                event.target.value = ''; // reset
            };
            reader.readAsText(file);
        },

        successCount() {
            return this.results.filter(r => r.success).length;
        },

        errorCount() {
            return this.results.filter(r => !r.success).length;
        },

        async startSending() {
            if (!this.apiKey) {
                alert('Você não tem uma API Key ativa. Verifique seu plano.');
                return;
            }

            const numbers = this.getNumbers();
            if (numbers.length === 0) {
                alert('Nenhum número válido encontrado. Use o formato DDD+Número.');
                return;
            }

            if (!this.message) {
                alert('Digite a mensagem.');
                return;
            }

            if (!confirm(`Deseja adicionar ${numbers.length} mensagens à fila de envio?`)) {
                return;
            }

            this.sending = true;
            this.results = [];
            this.progress = 0;

            for (let i = 0; i < numbers.length; i++) {
                const num = numbers[i];
                let result = { id: i, number: num, success: false, message: 'Processando...' };
                
                try {
                    const res = await fetch('/api/v1/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + this.apiKey
                        },
                        body: JSON.stringify({
                            to: num,
                            message: this.message,
                            media: this.media || null
                        })
                    });

                    const data = await res.json();
                    
                    if (res.ok) {
                        result.success = true;
                        result.message = data.status === 'scheduled' ? 'Agendado' : 'Na Fila';
                    } else {
                        result.success = false;
                        result.message = data.error || 'Falha';
                    }
                } catch (err) {
                    result.success = false;
                    result.message = 'Erro de Conexão';
                    console.error("Fetch error:", err);
                }

                this.results.unshift(result);
                this.progress = Math.round(((i + 1) / numbers.length) * 100);
                
                // Pequeno delay entre requests pra não afogar o backend de uma vez só
                await new Promise(r => setTimeout(r, 100));
            }

            this.sending = false;
        }
    }
}
</script>
@endsection
