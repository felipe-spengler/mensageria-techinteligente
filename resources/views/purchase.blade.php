<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratar - {{ $plan->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-gray-900 rounded-3xl p-8 border border-gray-800 shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold">Finalizar Contratação</h1>
            <p class="text-gray-400 text-sm">Plano: <span class="text-blue-400 font-semibold">{{ $plan->name }}</span> (R$ {{ number_format($plan->price, 2, ',', '.') }})</p>
        </div>

        <form id="purchaseForm" class="space-y-4">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
            
            @guest
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Nome Completo</label>
                    <input type="text" id="name" required class="w-full bg-gray-800 border-gray-700 rounded-xl p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">E-mail</label>
                    <input type="email" id="email" required class="w-full bg-gray-800 border-gray-700 rounded-xl p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">WhatsApp (com DDD)</label>
                    <input type="text" id="phone" placeholder="Ex: 5545999999999" required class="w-full bg-gray-800 border-gray-700 rounded-xl p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="text-[10px] text-gray-600 mt-1">Usaremos este número para avisos importantes sobre seu plano.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Senha de Acesso</label>
                    <input type="password" id="password" required class="w-full bg-gray-800 border-gray-700 rounded-xl p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            @else
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4 text-sm mb-6">
                    <p class="text-gray-400">Você está logado como:</p>
                    <p class="font-bold text-white">{{ auth()->user()->name }} ({{ auth()->user()->email }})</p>
                    <p class="text-[10px] text-blue-400 mt-1">O plano será vinculado automaticamente à sua conta.</p>
                </div>
            @endguest

            <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition duration-200 mt-4 shadow-lg shadow-blue-900/20">
                Gerar PIX de Pagamento
            </button>
        </form>

        <div id="pixContainer" class="hidden text-center space-y-6">
            <div class="bg-white p-4 rounded-2xl inline-block mx-auto">
                <img id="pixQr" src="" class="w-48 h-48 mx-auto">
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold">Escaneie o QR Code acima</p>
                <p class="text-xs text-gray-400">O seu acesso será liberado instantaneamente após a confirmação do pagamento.</p>
            </div>
            <div id="pixCopyContainer" class="hidden">
                <div class="bg-gray-800 rounded-lg p-3 text-xs break-all">
                    <p class="text-gray-500 mb-1">PIX Copia e Cola:</p>
                    <p id="pixPayload" class="text-white font-mono"></p>
                </div>
                <button onclick="copyPixPayload()" class="mt-2 bg-gray-700 hover:bg-gray-600 text-white text-xs px-4 py-2 rounded-lg transition">
                    Copiar Código PIX
                </button>
            </div>
            <div class="animate-pulse text-blue-400 text-xs font-medium">
                Aguardando pagamento...
            </div>
        </div>

        <div id="successMsg" class="hidden text-center space-y-4">
            <div class="w-16 h-16 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-xl font-bold">Pagamento Confirmado!</h2>
            <p class="text-gray-400 text-sm">Sua conta foi ativada e sua Chave de API foi gerada. Você será redirecionado para o painel.</p>
            <a href="/admin/login" class="inline-block bg-gray-800 px-6 py-2 rounded-lg text-sm font-bold">Ir para o Painel</a>
        </div>
    </div>

    <script>
        let pixPayloadGlobal = '';

        document.getElementById('purchaseForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = 'Processando...';

            const data = {
                plan_id: '{{ $plan->id }}',
                _token: '{{ csrf_token() }}'
            };

            // Only add registration fields if the user is not logged in
            if (!{{ auth()->check() ? 'true' : 'false' }}) {
                data.name = document.getElementById('name').value;
                data.email = document.getElementById('email').value;
                data.phone = document.getElementById('phone').value;
                data.password = document.getElementById('password').value;
            }

            const response = await fetch('/purchase', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                // Exibe QR Code
                const qrImg = document.getElementById('pixQr');
                if (result.qr_code_image) {
                    // QR Code do Asaas (base64)
                    qrImg.src = 'data:image/png;base64,' + result.qr_code_image;
                } else if (result.pix_payload) {
                    // Gera QR Code via API externa
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(result.pix_payload)}`;
                } else {
                    // Fallback
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(result.qr_code)}`;
                }

                // Exibe PIX Copia e Cola se disponível
                if (result.pix_payload) {
                    pixPayloadGlobal = result.pix_payload;
                    document.getElementById('pixPayload').textContent = result.pix_payload;
                    document.getElementById('pixCopyContainer').classList.remove('hidden');
                }

                document.getElementById('purchaseForm').classList.add('hidden');
                document.getElementById('pixContainer').classList.remove('hidden');
                startPolling(result.txid);
            } else {
                alert(result.error || 'Erro ao processar. Verifique os dados.');
                btn.disabled = false;
                btn.innerHTML = 'Gerar PIX de Pagamento';
            }
        });

        function copyPixPayload() {
            navigator.clipboard.writeText(pixPayloadGlobal).then(() => {
                alert('Código PIX copiado!');
            });
        }

        function startPolling(txid) {
            const interval = setInterval(async () => {
                const response = await fetch(`/pix/status/${txid}`);
                const result = await response.json();
                if (result.status === 'paid') {
                    clearInterval(interval);
                    document.getElementById('pixContainer').classList.add('hidden');
                    document.getElementById('successMsg').classList.remove('hidden');
                    setTimeout(() => window.location.href = '/admin/login', 5000);
                }
            }, 3000);
        }
    </script>
</body>
</html>
