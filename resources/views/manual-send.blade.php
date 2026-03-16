<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio Manual - Mensageria TechInteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-gray-800 rounded-2xl shadow-2xl p-8 border border-gray-700">
        <h1 class="text-2xl font-bold text-center mb-6">Envio de WhatsApp</h1>
        
        <form id="sendForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Destinatário (WhatsApp)</label>
                <input type="text" id="to" placeholder="Ex: 5545999999999" class="w-full bg-gray-700 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Mensagem</label>
                <textarea id="message" rows="4" placeholder="Sua mensagem aqui..." class="w-full bg-gray-700 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Imagem/Mídia (Base64 ou Link)</label>
                <input type="text" id="media" placeholder="Opcional: link da imagem" class="w-full bg-gray-700 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition duration-200">
                Pagar e Enviar (R$ 5,00)
            </button>
        </form>

        <div id="pixContainer" class="hidden mt-6 text-center animate-pulse">
            <p class="text-blue-400 font-semibold mb-2">Aguardando Pagamento PIX...</p>
            <div class="bg-white p-4 rounded-xl inline-block mb-4">
                <!-- QR Code Simulado -->
                <div class="w-48 h-48 bg-gray-200 flex items-center justify-center text-gray-500 text-xs">QR Code PIX Aqui</div>
            </div>
            <p class="text-xs text-gray-400 mb-2">Após o pagamento, a mensagem será enviada automaticamente.</p>
            <button id="copyPix" class="text-sm text-blue-400 hover:underline">Copiar Código PIX</button>
        </div>
    </div>

    <script>
        document.getElementById('sendForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                to: document.getElementById('to').value,
                message: document.getElementById('message').value,
                media: document.getElementById('media').value,
            };

            const response = await fetch('/manual-send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                document.getElementById('sendForm').classList.add('hidden');
                document.getElementById('pixContainer').classList.remove('hidden');
                startPolling(result.txid);
            }
        });

        function startPolling(txid) {
            const interval = setInterval(async () => {
                const response = await fetch(`/pix/status/${txid}`);
                const result = await response.json();
                if (result.status === 'paid') {
                    clearInterval(interval);
                    alert('Pagamento confirmado! Mensagem enviada.');
                    window.location.reload();
                }
            }, 3000);
        }
    </script>
</body>
</html>
