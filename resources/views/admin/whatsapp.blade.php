@extends('layouts.admin')

@section('title', 'Conectar WhatsApp')

@section('content')
    <div class="flex flex-col items-center justify-center space-y-12 py-10" x-data="whatsappManager()">
        
        <div class="text-center max-w-xl mx-auto">
            <h3 class="text-3xl font-bold text-white mb-2 tracking-tight">Vincule seu Dispositivo</h3>
            <p class="text-gray-400 text-sm mb-4">Sessão: <span class="text-blue-400 font-mono">{{ $instance->session_name }}</span></p>
            <p class="text-gray-400 text-sm leading-relaxed">Escaneie o QR Code abaixo com o seu celular para começar a enviar mensagens via API. O status será atualizado em tempo real.</p>
        </div>

        @php
            $apiKey = Auth::user()->apiKeys()->where('status', 'active')->first();
        @endphp

        @if($apiKey)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl px-4 mb-8">
            <!-- API Key Card -->
            <div class="glass rounded-3xl p-6 border-dash-700 shadow-xl flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Sua API Key</h4>
                    <div class="flex items-center space-x-3 bg-black/20 p-4 rounded-2xl border border-white/5">
                        <code class="text-blue-400 font-mono text-sm break-all flex-1">{{ $apiKey->key }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $apiKey->key }}')" class="text-gray-500 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Schedule Card -->
            <div class="glass rounded-3xl p-6 border-dash-700 shadow-xl">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Horário de Envio</h4>
                <form action="{{ route('admin.whatsapp.schedule') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex flex-col space-y-2">
                        <label class="flex items-center space-x-3 cursor-pointer group">
                            <input type="radio" name="schedule_type" value="full_time" @checked($instance->schedule_type === 'full_time') class="hidden peer" onchange="this.form.submit()">
                            <div class="w-5 h-5 rounded-full border-2 border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-500 transition-all flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                            </div>
                            <span class="text-sm text-gray-400 group-hover:text-white transition-colors">24/7 (Full-time)</span>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer group">
                            <input type="radio" name="schedule_type" value="business_hours" @checked($instance->schedule_type === 'business_hours') class="hidden peer" onchange="this.form.submit()">
                            <div class="w-5 h-5 rounded-full border-2 border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-500 transition-all flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                            </div>
                            <span class="text-sm text-gray-400 group-hover:text-white transition-colors">Horário Comercial (Seg-Sex, 08h-18h)</span>
                        </label>
                    </div>
                </form>
            </div>

        </div>
        @endif

        <div class="w-full max-w-4xl px-4 mb-8">
            <!-- Webhook Card -->
            <div class="glass rounded-3xl p-6 border-dash-700 shadow-xl">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Webhooks de Integração (API)</h4>
                <form action="{{ route('admin.whatsapp.webhook') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex flex-col space-y-2">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">URL de Notificação de Status</label>
                        <div class="flex flex-col md:flex-row items-center space-y-3 md:space-y-0 md:space-x-3">
                            <input type="url" name="webhook_url" value="{{ Auth::user()->webhook_url }}" placeholder="https://seu-sistema.com/webhook-whatsapp" class="w-full bg-black/20 p-4 rounded-2xl border border-white/5 text-sm text-blue-400 font-mono focus:outline-none focus:border-blue-500/50 transition-all">
                            <button type="submit" class="w-full md:w-auto px-10 py-4 bg-blue-600 hover:bg-blue-700 rounded-2xl text-[10px] font-bold text-white uppercase tracking-widest transition-all">
                                Salvar Webhook
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-500 italic mt-2">Enviaremos um POST para esta URL sempre que uma mensagem mudar de status (sent/failed). Útil para capturar erros de envio no seu sistema.</p>
                    </div>
                </form>
            </div>
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
                        <!-- Mostra o spinner se estiver inicializando ou se o status for QR_READY mas a imagem ainda não baixou -->
                        <template x-if="status === 'INITIALIZING' || status === 'CONNECTING' || (status === 'QR_READY' && !qrCode)">
                            <div class="flex flex-col items-center">
                                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"></div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest text-center">Iniciando motor WhatsApp<br><span class="text-[8px] opacity-50 text-blue-400">O QR Code aparecerá em instantes...</span></p>
                            </div>
                        </template>
                        
                        <!-- Mostra apenas o ícone de pronto se estiver totalmente offline -->
                        <template x-if="(status === 'OFFLINE' || status === 'DISCONNECTED') && !loading">
                            <div class="flex flex-col items-center">
                                <div class="bg-blue-600/10 p-4 rounded-full mb-2">
                                     <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                </div>
                                <p class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">Pronto para Conectar</p>
                            </div>
                        </template>
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
                <div class="mt-12 flex flex-col space-y-4 w-full">
                    <div class="flex space-x-4 justify-center">
                        <button @click="refreshStatus" :disabled="loading" class="px-8 py-3 bg-dash-900 hover:bg-dash-800 border border-white/5 rounded-2xl text-[10px] font-bold text-gray-300 uppercase tracking-widest transition-all">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Atualizar</span>
                        </button>
                        <template x-if="status === 'OFFLINE' || status === 'DISCONNECTED'">
                            <button @click="startConnection" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 rounded-2xl text-[10px] font-bold text-white uppercase tracking-widest transition-all">
                                Iniciar Conexão
                            </button>
                        </template>
                        <button x-show="status === 'CONNECTED'" @click="logoutWhatsApp" class="px-8 py-3 bg-red-600/10 hover:bg-red-600/20 border border-red-500/20 rounded-2xl text-[10px] font-bold text-red-400 uppercase tracking-widest transition-all">
                            Desconectar
                        </button>
                    </div>
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
                    this.pollingToken = setInterval(() => this.refreshStatus(), 5000);
                    
                    // Auto-start if offline
                    setTimeout(() => {
                        if (this.status === 'OFFLINE' || this.status === 'DISCONNECTED') {
                            this.startConnection();
                        }
                    }, 2000);
                },

                async refreshStatus() {
                    if (this.status === 'CONNECTED' && this.loading) return;
                    this.loading = true;
                    try {
                        const res = await fetch('/admin/bridge/status');
                        const data = await res.json();
                        
                        // Mapeamento amigável de status para a UI
                        let newStatus = (data.status || 'OFFLINE').toUpperCase();
                        const successStates = ['CONNECTED', 'ISLOGGED', 'LOGGED', 'AUTHENTICATED', 'SYNCING', 'INCHAT'];
                        
                        if (successStates.includes(newStatus)) {
                            this.status = 'CONNECTED';
                        } else {
                            this.status = newStatus;
                        }
                        
                        if (this.status === 'QR_READY') {
                            await this.fetchQrCode();
                        } else {
                            this.qrCode = null;
                        }
                    } catch(e) { console.error('Status Error:', e.message); }
                    this.loading = false;
                },

                async startConnection() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('admin.whatsapp.start') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        });
                        if (res.ok) {
                            this.status = 'INITIALIZING';
                            // Intervalo mais rápido durante a inicialização
                            let attempts = 0;
                            const quickPoll = setInterval(async () => {
                                await this.refreshStatus();
                                attempts++;
                                if (this.status === 'QR_READY' || this.status === 'CONNECTED' || attempts > 30) {
                                    clearInterval(quickPoll);
                                }
                            }, 1500);
                        }
                    } catch(e) { console.error('Start Error:', e.message); }
                    this.loading = false;
                },

                async fetchQrCode() {
                    if (this.status === 'CONNECTED') return;
                    try {
                        const res = await fetch('/admin/bridge/qrcode');
                        if (res.ok && res.headers.get('content-type').includes('image/png')) {
                            const blob = await res.blob();
                            this.qrCode = URL.createObjectURL(blob);
                        } else if (res.status === 404) {
                            // Se o QR sumiu (404), pode ser que tenha acabado de conectar
                            console.log('QR Code 404. Checking if connected...');
                            this.refreshStatus();
                        }
                    } catch(e) { console.error('QR Error:', e.message); }
                },

                async logoutWhatsApp() {
                    if (!confirm('Deseja realmente desconectar este WhatsApp?')) return;
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('admin.whatsapp.logout') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        });
                        if (res.ok) {
                            this.status = 'DISCONNECTED';
                            this.qrCode = null;
                            this.refreshStatus();
                        }
                    } catch(e) { console.error('Logout Error:', e.message); }
                    this.loading = false;
                }
            }
        }
    </script>
    @endpush
@endsection
