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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Envio de WhatsApp</h1>
            @if(!$hasUsedFree)
                <span class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded-full font-bold animate-pulse border border-green-500/30">TESTE GRÁTIS</span>
            @endif
        </div>
        
        <form id="sendForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Destinatário(s)</label>
                <input type="text" id="to" placeholder="Ex: 5545999999999, 5545888888888" class="w-full bg-gray-700 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <p class="text-[10px] text-gray-500 mt-1">Separe os números por vírgula para envios múltiplos.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Mensagem</label>
                <div class="bg-gray-700 rounded-lg overflow-hidden border border-gray-600 focus-within:ring-2 focus-within:ring-blue-500">
                    <div class="flex items-center space-x-2 p-2 bg-gray-750 border-b border-gray-600">
                        <button type="button" onclick="insertFormat('*')" title="Negrito" class="p-1 hover:bg-gray-600 rounded text-xs font-bold w-8">B</button>
                        <button type="button" onclick="insertFormat('_')" title="Itálico" class="p-1 hover:bg-gray-600 rounded text-xs italic w-8">I</button>
                        <button type="button" onclick="insertFormat('~')" title="Tachado" class="p-1 hover:bg-gray-600 rounded text-xs line-through w-8">S</button>
                        <div class="h-4 w-px bg-gray-600 mx-1"></div>
                        <button type="button" onclick="insertEmoji('🚀')" class="p-1 hover:bg-gray-600 rounded text-xs">🚀</button>
                        <button type="button" onclick="insertEmoji('✅')" class="p-1 hover:bg-gray-600 rounded text-xs">✅</button>
                        <button type="button" onclick="insertEmoji('👋')" class="p-1 hover:bg-gray-600 rounded text-xs">👋</button>
                        <button type="button" onclick="insertEmoji('⭐')" class="p-1 hover:bg-gray-600 rounded text-xs">⭐</button>
                    </div>
                    <textarea id="message" rows="4" placeholder="Sua mensagem aqui..." class="w-full bg-transparent border-none p-3 text-white outline-none resize-none"></textarea>
                </div>
                <p class="text-[10px] text-gray-500 mt-1">Dica: Use *texto* para negrito e _texto_ para itálico.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Imagem/Mídia (Link)</label>
                <input type="text" id="media" placeholder="Opcional: link da imagem" class="w-full bg-gray-700 border-none rounded-lg p-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition duration-200">
                {{ !$hasUsedFree ? 'Enviar Teste Grátis' : 'Comprar Pacote (R$ 5,00)' }}
            </button>
            <p class="text-[10px] text-center text-gray-500">
                {{ !$hasUsedFree ? 'O primeiro envio é por nossa conta!' : 'Pacote mínimo: 5 envios por R$ 5,00.' }}
            </p>
        </form>

        <div id="pixContainer" class="hidden mt-6 text-center animate-pulse">
            <p class="text-blue-400 font-semibold mb-2">Aguardando Pagamento PIX...</p>
            <div class="bg-white p-4 rounded-xl inline-block mb-4">
                <div class="w-48 h-48 bg-gray-200 flex items-center justify-center text-gray-500 text-xs">QR Code PIX Aqui</div>
            </div>
            <p class="text-xs text-gray-400 mb-2">Após o pagamento de R$ 5,00, as mensagens serão enviadas.</p>
            <button id="copyPix" class="text-sm text-blue-400 hover:underline">Copiar Código PIX</button>
        </div>

        <div id="successMsg" class="hidden mt-6 text-center">
            <div class="text-green-500 text-5xl mb-2">✓</div>
            <h2 class="text-xl font-bold">Sucesso!</h2>
            <p class="text-gray-400 text-sm">Suas mensagens foram colocadas na fila de envio.</p>
            <button onclick="window.location.reload()" class="mt-4 text-blue-400 text-xs hover:underline">Fazer novo envio</button>
        </div>
    </div>

    <script>
        function insertFormat(char) {
            const textarea = document.getElementById('message');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selected = text.substring(start, end);
            
            textarea.value = text.substring(0, start) + char + selected + char + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + 1, end + 1);
        }

        function insertEmoji(emoji) {
            const textarea = document.getElementById('message');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            textarea.value = text.substring(0, start) + emoji + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + emoji.length, start + emoji.length);
        }

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
                if (result.type === 'free') {
                    document.getElementById('sendForm').classList.add('hidden');
                    document.getElementById('successMsg').classList.remove('hidden');
                } else {
                    document.getElementById('sendForm').classList.add('hidden');
                    document.getElementById('pixContainer').classList.remove('hidden');
                    startPolling(result.txid);
                }
            }
        });

        function startPolling(txid) {
            const interval = setInterval(async () => {
                const response = await fetch(`/pix/status/${txid}`);
                const result = await response.json();
                if (result.status === 'paid') {
                    clearInterval(interval);
                    document.getElementById('pixContainer').classList.add('hidden');
                    document.getElementById('successMsg').classList.remove('hidden');
                }
            }, 3000);
        }
    </script>
</body>
</html>
