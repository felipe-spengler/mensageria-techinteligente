<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enviar API (test)</title>
    <style>
        body { background: #0b1225; color: #f8fafc; font-family: Arial, sans-serif; padding: 2rem; }
        .card { background: #131f45; border: 1px solid #2f4d99; padding: 1.2rem; border-radius: 8px; max-width: 650px; margin: auto; }
        button { cursor: pointer; background: #06b6d4; border: 0; color: white; padding: 0.8rem 1rem; border-radius: 6px; }
        button:disabled { background: #4b5563; }
        pre { background: #020617; color: #dbeafe; padding: 0.8rem; margin-top: 1rem; border-radius: 6px; overflow:auto; max-height: 280px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Enviar API - teste rápido</h1>
        <p>Requisição: <strong>to=45920014605</strong> message="testando"</p>
        <p>API Key usada no backend: <strong>test_key_master_123</strong></p>

        <button id="sendBtn">Enviar mensagem via /api/v1/send</button>

        <p id="status">Aguardando ação...</p>

        <pre id="log"></pre>
    </div>

    <script>
        const sendBtn = document.getElementById('sendBtn');
        const statusEl = document.getElementById('status');
        const logEl = document.getElementById('log');

        function writeLog(message) {
            console.log('[envia_api]', message);
            logEl.innerText += message + '\n';
        }

        sendBtn.addEventListener('click', async () => {
            sendBtn.disabled = true;
            statusEl.innerText = 'Enviando...';
            writeLog('Iniciando chamada para /envia_api/send');

            try {
                const response = await fetch('/envia_api/send', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                const data = await response.json();

                statusEl.innerText = 'Resposta recebida: ' + response.status;
                writeLog('HTTP ' + response.status);
                writeLog(JSON.stringify(data, null, 2));

                if (!response.ok) {
                    statusEl.innerText = 'Falha: veja console/log';
                } else {
                    statusEl.innerText = 'Sucesso: mensagem enfileirada (ou erro do API).';
                }
            } catch (error) {
                statusEl.innerText = 'Erro de rede ou servidor';
                writeLog('Erro catch: ' + error.toString());
            }

            sendBtn.disabled = false;
        });
    </script>
</body>
</html>