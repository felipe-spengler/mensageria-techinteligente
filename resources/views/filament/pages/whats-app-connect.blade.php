<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-filament::section>
            <x-slot name="heading">Conectar Dispositivo</x-slot>
            <div class="flex flex-col items-center justify-center p-6 space-y-4">
                <div id="qrcode-container" class="bg-white p-4 rounded-xl shadow-inner border border-gray-200">
                    <img src="/admin/bridge/qrcode?t={{ time() }}" alt="QR Code" id="qrcode-img" class="w-64 h-64">
                </div>
                <p class="text-sm text-gray-500 text-center">
                    Abra o WhatsApp no seu celular, vá em Aparelhos Conectados e escaneie o código acima.
                </p>
                <div id="status-badge" class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider bg-yellow-500/20 text-yellow-500 border border-yellow-500/30">
                    Aguardando Conexão...
                </div>
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" onclick="window.location.reload()">
                    Atualizar QR Code
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Configurações de Webhook</x-slot>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-400">URL do Webhook</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-700 bg-gray-800 px-3 text-gray-500 sm:text-sm">
                            POST
                        </span>
                        <input type="text" readonly value="{{ url('/api/v1/webhook') }}" class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-gray-700 bg-gray-900 text-white sm:text-sm focus:ring-blue-500 border-l-0">
                    </div>
                    <p class="mt-2 text-[10px] text-gray-500">
                        Use esta URL para receber notificações de mensagens recebidas.
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-400">Token de Segurança</label>
                    <p class="text-xs text-gray-300 mt-1 font-mono bg-gray-950 p-2 rounded border border-gray-800">
                        {{ \App\Models\Setting::where('key', 'webhook_token')->first()?->value ?? 'Não configurado' }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

    <script>
        const qrcodeContainer = document.getElementById('qrcode-container');
        const statusBadge = document.getElementById('status-badge');

        function setStatus(status, isConnected) {
            statusBadge.innerHTML = status;
            statusBadge.className = isConnected
                ? 'px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider bg-green-500/20 text-green-500 border border-green-500/30'
                : 'px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider bg-red-500/20 text-red-500 border border-red-500/30';

            if (isConnected) {
                qrcodeContainer.classList.add('hidden');
            } else {
                qrcodeContainer.classList.remove('hidden');
            }
        }

        setInterval(async () => {
            try {
                const response = await fetch('/admin/bridge/status');
                if (!response.ok) {
                    setStatus('Erro de status', false);
                    return;
                }

                const data = await response.json();
                const status = (data.status || '').toString().toLowerCase();
                const connectedStates = ['connected', 'islogged', 'logged', 'authenticated', 'qr_read_success'];
                const qrStates = ['qr_ready', 'qrcode', 'scan_qr', 'qr'];

                if (connectedStates.includes(status)) {
                    setStatus('Conectado', true);
                } else if (qrStates.includes(status)) {
                    setStatus('Aguardando QR', false);
                } else {
                    setStatus('Desconectado', false);
                }
            } catch (e) {
                setStatus('Bridge offline', false);
            }
        }, 5000);
    </script>
</x-filament-panels::page>
