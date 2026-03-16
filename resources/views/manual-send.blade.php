<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio Particular - TechInteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(12px); }
        .gradient-border {
            position: relative;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 1.5rem;
        }
        .gradient-border::before {
            content: ""; position: absolute; inset: -1px;
            background: linear-gradient(45deg, #3b82f6, #8b5cf6);
            border-radius: 1.5rem; z-index: -1; opacity: 0.5;
        }
    </style>
</head>
<body class="bg-[#030712] text-white min-h-screen selection:bg-blue-500/30">
    <!-- Efeito de fundo -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-purple-600/10 rounded-full blur-[120px]"></div>
    </div>

    <nav class="relative z-20 p-8 flex justify-between items-center max-w-7xl mx-auto">
        <div class="text-2xl font-black tracking-tighter flex items-center gap-2">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">T</div>
                <span>TechInteligente</span>
            </a>
        </div>
        <div class="hidden md:flex gap-8 text-sm font-medium text-gray-400">
            <a href="/enviar" class="text-white font-bold transition">Envio Particular</a>
            <a href="/#planos" class="hover:text-white transition">Preços</a>
            @auth
                <a href="/admin" class="text-blue-400 font-bold hover:text-blue-300 transition">Meu Painel</a>
            @else
                <a href="/admin/login" class="hover:text-white transition">Login</a>
            @endauth
        </div>
        <a href="/#planos" class="bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-blue-500 transition">SaaS API</a>
    </nav>

    <main class="relative z-10 max-w-7xl mx-auto px-6 py-12 flex flex-col items-center">
        <div class="max-w-xl w-full">
            <header class="text-center mb-10">
                <h1 class="text-4xl font-black mb-4">Envio Avulso Instantâneo</h1>
                <p class="text-gray-400">Sem mensalidade. Pague por disparo via PIX ou use seu teste grátis.</p>
            </header>

            <div class="gradient-border p-8 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Nova Mensagem</h2>
                    @if(!$hasUsedFree)
                        <span class="bg-green-500/20 text-green-400 text-[10px] px-3 py-1 rounded-full font-black border border-green-500/30 uppercase tracking-widest">Teste Grátis Disponível</span>
                    @endif
                </div>

                <form id="sendForm" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Destinatário(s)</label>
                        <input type="text" id="to" placeholder="Ex: 5545999999999 (com DDD e 55)" class="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Mensagem Inteligente</label>
                        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 transition">
                            <div class="flex items-center space-x-2 p-3 bg-white/5 border-b border-white/10">
                                <button type="button" onclick="insertFormat('*')" class="w-8 h-8 hover:bg-white/10 rounded font-bold text-sm">B</button>
                                <button type="button" onclick="insertFormat('_')" class="w-8 h-8 hover:bg-white/10 rounded italic text-sm">I</button>
                                <button type="button" onclick="insertEmoji('🚀')" class="w-8 h-8 hover:bg-white/10 rounded">🚀</button>
                                <button type="button" onclick="insertEmoji('✅')" class="w-8 h-8 hover:bg-white/10 rounded">✅</button>
                            </div>
                            <textarea id="message" rows="5" placeholder="Digite sua mensagem aqui..." class="w-full bg-transparent border-none p-4 text-white outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Anexo de Mídia</label>
                        <input type="file" id="mediaFile" accept="image/*,application/pdf" class="w-full text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                        <input type="hidden" id="mediaBase64">
                        <div id="imagePreview" class="hidden mt-4 border border-white/10 rounded-2xl overflow-hidden bg-black/50 relative p-2">
                             <img src="" id="previewImg" class="max-h-40 mx-auto rounded-lg">
                             <button type="button" onclick="clearMedia()" class="absolute top-4 right-4 bg-red-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold">×</button>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl transition duration-300 shadow-xl shadow-blue-600/20 uppercase tracking-widest">
                        {{ !$hasUsedFree ? 'Enviar Agora (Grátis)' : 'Comprar Créditos (R$ 5,00)' }}
                    </button>
                </form>

                <!-- PIX Container -->
                <div id="pixContainer" class="hidden mt-8 text-center animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="bg-blue-600/10 border border-blue-500/20 rounded-3xl p-8">
                        <p class="text-blue-400 font-bold mb-4 uppercase tracking-tighter">Aguardando Pagamento PIX</p>
                        <div class="bg-white p-4 rounded-2xl inline-block mb-6 shadow-2xl">
                             <div class="w-48 h-48 bg-gray-100 flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-12 h-12 mb-2 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v1m0 11v1m8-5h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <span class="text-[10px] font-bold">GERANDO QR CODE...</span>
                             </div>
                        </div>
                        <p class="text-xs text-gray-400 mb-4">Valor: <span class="text-white font-bold text-lg">R$ 5,00</span> (5 Envios)</p>
                        <button id="copyPix" class="w-full py-3 bg-white/10 hover:bg-white/20 border border-white/10 rounded-xl text-xs font-bold transition">Copiar Código PIX</button>
                    </div>
                </div>

                <!-- Success Msg -->
                <div id="successMsg" class="hidden mt-8 text-center py-10">
                    <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-500/20">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h2 class="text-2xl font-black mb-2">Mensagem Enviada!</h2>
                    <p class="text-gray-400 text-sm mb-8">Seu disparo está sendo processado agora mesmo.</p>
                    <button onclick="window.location.reload()" class="px-8 py-3 bg-blue-600 hover:bg-blue-500 rounded-xl font-bold transition">Novo Envio</button>
                </div>
            </div>
            
            <footer class="mt-12 text-center text-gray-600 text-[10px] uppercase tracking-[0.2em]">
                &copy; 2026 TechInteligente Secure Messaging
            </footer>
        </div>
    </main>

    <script>
        function insertFormat(char) {
            const textarea = document.getElementById('message');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + char + text.substring(start, end) + char + text.substring(end);
            textarea.focus();
        }

        function insertEmoji(emoji) {
            const textarea = document.getElementById('message');
            const start = textarea.selectionStart;
            textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(start);
            textarea.focus();
        }

        document.getElementById('mediaFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (event) => {
                document.getElementById('mediaBase64').value = event.target.result;
                document.getElementById('previewImg').src = event.target.result;
                document.getElementById('imagePreview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });

        function clearMedia() {
            document.getElementById('mediaFile').value = '';
            document.getElementById('mediaBase64').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
        }

        document.getElementById('sendForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true; btn.innerHTML = 'Enviando...';

            const resp = await fetch('/manual-send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    to: document.getElementById('to').value,
                    message: document.getElementById('message').value,
                    media: document.getElementById('mediaBase64').value
                })
            });

            const res = await resp.json();
            if (res.success) {
                document.getElementById('sendForm').classList.add('hidden');
                if (res.type === 'free') {
                    document.getElementById('successMsg').classList.remove('hidden');
                } else {
                    document.getElementById('pixContainer').classList.remove('hidden');
                    startPolling(res.txid);
                }
            }
        });

        function startPolling(txid) {
            const interval = setInterval(async () => {
                const r = await fetch(`/pix/status/${txid}`);
                const data = await r.json();
                if (data.status === 'paid') {
                    clearInterval(interval);
                    document.getElementById('pixContainer').classList.add('hidden');
                    document.getElementById('successMsg').classList.remove('hidden');
                }
            }, 3000);
        }
    </script>
</body>
</html>
