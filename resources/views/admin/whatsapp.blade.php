@extends('layouts.admin')

@section('title', 'Conectar WhatsApp')

@section('content')
    <div class="flex flex-col items-center justify-center space-y-12 py-10" x-data="whatsappManager()">
        
        <div class="text-center max-w-xl mx-auto">
            <h3 class="text-3xl font-bold text-white mb-4 tracking-tight">Vincule seu Dispositivo</h3>
            <p class="text-gray-400 text-sm">Escaneie o QR Code abaixo com o seu celular para começar a enviar mensagens automaticamente via API. O status será atualizado em tempo real.</p>
        </div>

        <!-- Connection Status Card -->
        <div class="glass w-full max-w-lg rounded-[48px] p-12 border-dash-700 shadow-3xl text-center relative overflow-hidden">
            <!-- Pulsing Background -->
            <div x-show="status === 'CONNECTED'" class="absolute inset-0 bg-emerald-500/5 blur-3xl rounded-full"></div>
            
            <div class="relative z-10 flex flex-col items-center">
                
                <!-- Status Badge -->
                <div class="mb-8">
                    <template x-if="status === 'CONNECTED'">
                        <div class="inline-flex items-center space-x-2 px-6 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest shadow-lg shadow-emerald-900/10">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Dispositivo Online</span>
                        </div>
                    </template>
                    <template x-if="status !== 'CONNECTED'">
                        <div class="inline-flex items-center space-x-2 px-6 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold uppercase tracking-widest shadow-lg shadow-amber-900/10">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Aguardando Conexão</span>
                        </div>
                    </template>
                </div>

                <!-- QR Code Display -->
                <div x-show="status !== 'CONNECTED'" class="p-6 bg-white rounded-[32px] shadow-2xl transition-all hover:scale-105">
                    <div x-show="qrCode" class="w-64 h-64 flex items-center justify-center overflow-hidden">
                        <img :src="qrCode" alt="QR Code WhatsApp" class="w-full h-full object-contain">
                    </div>
                    <div x-show="!qrCode" class="w-64 h-64 flex flex-col items-center justify-center space-y-4">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Gerando Código...</p>
                    </div>
                </div>

                <!-- Connected Display -->
                <div x-show="status === 'CONNECTED'" class="space-y-6">
                    <div class="w-24 h-24 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-500/20">
                        <svg class="w-12 h-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-xl font-bold text-white">Pronto para Enviar!</h4>
                        <p class="text-xs text-gray-500">Seu número está vinculado e pronto para as requisições API.</p>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="mt-12 flex space-x-4">
                    <button @click="refreshStatus" :disabled="loading" class="px-8 py-3 bg-dash-900 hover:bg-dash-800 border border-white/5 rounded-2xl text-[10px] font-bold text-gray-300 uppercase tracking-widest transition-all">
                        <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Atualizar</span>
                    </button>
                    <button x-show="status === 'CONNECTED'" @click="logoutWhatsApp" class="px-8 py-3 bg-red-600/10 hover:bg-red-600/20 border border-red-500/20 rounded-2xl text-[10px] font-bold text-red-400 uppercase tracking-widest transition-all">
                        Desconectar
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-blue-500/5 p-6 rounded-3xl border border-blue-500/10 text-center max-w-sm">
            <p class="text-[10px] text-blue-400 leading-relaxed font-medium">Lembre-se: Use um número secundário para evitar banimentos por SPAM. Não envie mensagens robóticas em massa para contatos que não solicitaram.</p>
        </div>
    </div>

    @push('scripts')
    <script>
        function whatsappManager() {
            return {
                status: 'DISCONNECTED',
                qrCode: null,
                loading: false,
                pollingToken: null,

                init() {
                    this.refreshStatus();
                    this.pollingToken = setInterval(() => this.refreshStatus(), 10000);
                },

                async refreshStatus() {
                    this.loading = true;
                    try {
                        const res = await fetch('/admin/bridge/status');
                        const data = await res.json();
                        this.status = data.status || 'DISCONNECTED';
                        
                        if (this.status !== 'CONNECTED') {
                            await this.fetchQrCode();
                        } else {
                            this.qrCode = null;
                        }
                    } catch(e) { console.error(e); }
                    this.loading = false;
                },

                async fetchQrCode() {
                    try {
                        const res = await fetch('/admin/bridge/qrcode');
                        if (res.ok && res.headers.get('content-type').includes('image/png')) {
                            const blob = await res.blob();
                            this.qrCode = URL.createObjectURL(blob);
                        } else {
                            const data = await res.json().catch(() => null);
                            if (data && data.qrcode) {
                                this.qrCode = data.qrcode;
                            }
                        }
                    } catch(e) { console.error(e); }
                },

                async logoutWhatsApp() {
                    if (!confirm('Deseja realmente desconectar este WhatsApp?')) return;
                    // Implement logic if needed
                }
            }
        }
    </script>
    @endpush
@endsection
