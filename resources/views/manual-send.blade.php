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

    <nav class="relative z-30 p-8 flex justify-between items-center max-w-7xl mx-auto">
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

        <div class="flex items-center gap-4">
            <a href="/#planos" class="hidden sm:block bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-blue-500 transition">SaaS API</a>
            
            <button onclick="toggleMobileMenu()" class="md:hidden text-gray-400 hover:text-white p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobileMenu" class="fixed inset-0 bg-[#030712]/95 backdrop-blur-xl z-50 hidden flex-col items-center justify-center gap-8 text-2xl font-black">
            <button onclick="toggleMobileMenu()" class="absolute top-8 right-8 text-gray-400 hover:text-white">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <a href="/enviar" onclick="toggleMobileMenu()" class="hover:text-blue-500 transition">Envio Particular</a>
            <a href="/#planos" onclick="toggleMobileMenu()" class="hover:text-blue-500 transition">Preços</a>
            <a href="/admin/login" onclick="toggleMobileMenu()" class="hover:text-blue-500 transition">Login</a>
            <a href="/#planos" onclick="toggleMobileMenu()" class="bg-blue-600 text-white px-8 py-3 rounded-full">SaaS API</a>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('flex');
            document.body.classList.toggle('overflow-hidden');
        }
    </script>

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

                <div id="healthBox" class="mb-6 p-4 bg-black/30 border border-blue-400/30 rounded-xl">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-bold text-blue-200">Health Check</h3>
                        <button type="button" id="refreshHealth" class="text-xs px-3 py-1 bg-blue-500/30 border border-blue-400/30 rounded">Refresh</button>
                    </div>
                    <div id="healthStatus" class="text-xs text-gray-200">Loading...</div>
                    <pre id="healthDetails" class="text-xs font-mono mt-2 p-2 bg-black/50 border border-gray-700 rounded max-h-40 overflow-auto"></pre>
                </div>

                <form id="sendForm" class="space-y-6">
                    <div id="errorMsg" class="hidden text-red-400 text-sm p-3 bg-red-500/10 border border-red-500/20 rounded-lg"></div>
                    <div id="debugStatus" class="hidden text-xs font-mono text-gray-100 p-2 bg-gray-800/80 border border-gray-700 rounded-lg mb-2"></div>
                    <div id="debugReqRes" class="hidden bg-gray-900/80 border border-gray-700 rounded-lg p-3 text-xs font-mono text-gray-100 space-y-2 max-h-72 overflow-auto">
                        <div><strong>Request envoyée</strong></div>
                        <pre id="debugRequest" class="bg-black/70 border border-gray-500 p-2 rounded overflow-auto"></pre>
                        <div><strong>Response recebida</strong></div>
                        <pre id="debugResponse" class="bg-black/70 border border-gray-500 p-2 rounded overflow-auto"></pre>
                    </div>
                    <div id="debugMsg" class="hidden text-xs font-mono text-gray-100 p-3 bg-gray-900/80 border border-gray-700 rounded-lg whitespace-pre-wrap overflow-auto max-h-36"></div>
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
            const errorMsg = document.getElementById('errorMsg');
            const debugMsg = document.getElementById('debugMsg');
            const debugStatus = document.getElementById('debugStatus');
            const debugReqRes = document.getElementById('debugReqRes');
            const debugRequest = document.getElementById('debugRequest');
            const debugResponse = document.getElementById('debugResponse');

            errorMsg.classList.add('hidden');
            errorMsg.innerText = '';
            debugStatus.classList.remove('hidden');
            debugStatus.innerText = 'STEP 1: iniciando requisição';
            debugReqRes.classList.remove('hidden');
            debugRequest.innerText = '';
            debugResponse.innerText = '';
            debugMsg.classList.remove('hidden');
            debugMsg.innerText = 'STEP 1: coletando dados e iniciando request...';
            btn.disabled = true; btn.innerHTML = 'Enviando...';

            const payload = {
                to: document.getElementById('to').value,
                message: document.getElementById('message').value,
                media: document.getElementById('mediaBase64').value
            };

            console.group('manual-send');
            console.log('manual-send payload', payload);


            try {
                const resp = await fetch('/manual-send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(payload)
                });

                debugStatus.innerText = 'STEP 2: requisição enviada';
                debugRequest.innerText = JSON.stringify(payload, null, 2);
                const serverText = await resp.text();
                debugMsg.innerText = 'STEP 2: resposta recebida; status=' + resp.status + ' ' + resp.statusText + '\nAguardando parse JSON...';

                let res;
                try {
                    res = JSON.parse(serverText);
                    console.log('manual-send response JSON', res);
                    debugMsg.innerText = 'STEP 3: payload parseado com sucesso. response=' + JSON.stringify(res, null, 2);
                } catch (jsonError) {
                    console.error('manual-send JSON parse error', jsonError, 'server raw:', serverText);
                    debugMsg.innerText = 'ERRO STEP 3: resposta não foi JSON válido. status=' + resp.status + ' ' + resp.statusText + '\n' + serverText;
                    debugResponse.innerText = serverText || 'sem corpo';
                    debugStatus.innerText = 'STEP 3: inválido JSON';
                    throw new Error('Resposta inválida do servidor. Veja o console para mais detalhes.');
                }

                if (!resp.ok || !res.success) {
                    console.warn('manual-send failed', res);
                    const msg = res?.message || 'Erro ao enviar: status ' + resp.status;
                    const e = new Error(msg);
                    e.details = {
                        status: resp.status,
                        statusText: resp.statusText,
                        body: res
                    };
                    debugResponse.innerText = JSON.stringify(res, null, 2);
                    debugStatus.innerText = 'STEP 4: backend respondeu erro';
                    debugMsg.innerText = 'ERRO STEP 4: ' + msg + '\n' + JSON.stringify(e.details, null, 2);
                    throw e;
                }

                console.log('manual-send success', res);
                debugResponse.innerText = JSON.stringify(res, null, 2);
                debugStatus.innerText = 'STEP 4: backend respondeu sucesso';
                debugMsg.innerText = 'STEP 4: envio aceito pelo backend. ' + JSON.stringify(res, null, 2);
                document.getElementById('sendForm').classList.add('hidden');
                if (res.type === 'free') {
                    document.getElementById('successMsg').classList.remove('hidden');
                } else {
                    document.getElementById('pixContainer').classList.remove('hidden');
                    startPolling(res.txid);
                }
            } catch (error) {
                errorMsg.innerText = error.message;
                errorMsg.classList.remove('hidden');

                const debugMsg = document.getElementById('debugMsg');
                const debugStatus = document.getElementById('debugStatus');
                const debugResponse = document.getElementById('debugResponse');
                debugStatus.classList.remove('hidden');
                debugStatus.innerText = 'STEP FIM: erro geral';

                const payload = {
                    step_error: error.message,
                    details: error.details || error,
                    timestamp: new Date().toISOString(),
                    responseDump: debugResponse.innerText,
                };

                debugMsg.innerText = 'DEBUG /manual-send:\n' + JSON.stringify(payload, null, 2);
                debugMsg.classList.remove('hidden');

                console.error('manual-send error', payload);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Enviar Mensagem';
                console.groupEnd();
            }
        });

        async function checkHealth() {
            const statusEl = document.getElementById('healthStatus');
            const detailsEl = document.getElementById('healthDetails');

            statusEl.innerText = 'Verificando...';
            detailsEl.innerText = '';

            try {
                const r = await fetch('/bridge-health');
                const textBody = await r.text();

                let data;
                try {
                    data = JSON.parse(textBody);
                } catch (jsonError) {
                    data = null;
                }

                if (!data) {
                    console.error('checkHealth parse failed', textBody);
                    statusEl.innerText = `Health erro: status ${r.status} (${r.statusText})`;
                    detailsEl.innerText = textBody;
                    return null;
                }

                statusEl.innerText = `Bridge: ${data.bridge.status}, Redis: ${data.redis.status}`;
                detailsEl.innerText = JSON.stringify(data, null, 2);
                console.log('bridge-health', data);

                if (!r.ok) {
                    throw new Error(`Health API status ${r.status} - ${data.error || data.bridge?.details?.error || 'unknown'}`);
                }

                return data;
            } catch (e) {
                console.error('checkHealth error', e);
                statusEl.innerText = 'Falha ao checar health: ' + e.message;
                detailsEl.innerText = e.stack || String(e);
                return null;
            }
        }

        document.getElementById('refreshHealth').addEventListener('click', checkHealth);

        window.addEventListener('DOMContentLoaded', () => {
            checkHealth();
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
