const wppconnect = require('@wppconnect-team/wppconnect');
const Redis = require('ioredis');
const axios = require('axios');
const express = require('express');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

// ─────────────────────────────────────────────────────────────────────────────
// PROTECTION CONFIG
// ─────────────────────────────────────────────────────────────────────────────
const HEAP_LIMIT_MB        = parseInt(process.env.HEAP_LIMIT_MB        || '1400');  // boosted for production headroom
const WATCHDOG_INTERVAL_MS = parseInt(process.env.WATCHDOG_INTERVAL_MS || '30000'); // check every 30s
const AXIOS_TIMEOUT_MS     = parseInt(process.env.AXIOS_TIMEOUT_MS     || '30000'); // 30s per webhook call
const QUEUE_MAX_SIZE       = parseInt(process.env.QUEUE_MAX_SIZE       || '10000'); // increased queue depth
const RATE_LIMIT_MAX       = parseInt(process.env.RATE_LIMIT_MAX       || '20');    // relaxed for pro use
const RATE_LIMIT_WINDOW_MS = parseInt(process.env.RATE_LIMIT_WINDOW_MS || '60000'); // 1 min window

function isBusinessHours() {
    try {
        const brTime = new Date(new Date().toLocaleString("en-US", {timeZone: "America/Sao_Paulo"}));
        const day = brTime.getDay(); // 0 (Dom) a 6 (Sab)
        const hour = brTime.getHours();

        if (day === 0 || day === 6) return false;
        if (hour < 8 || hour >= 18) return false;
        return true;
    } catch (e) {
        console.error('[SCHEDULE] Error checking business hours:', e.message);
        return true; // Fallback
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// RATE LIMITER (per-number, in-memory)
// key: phone number → { count, windowStart }
// ─────────────────────────────────────────────────────────────────────────────
const rateLimitMap = new Map();

function isRateLimited(to) {
    const now = Date.now();
    const entry = rateLimitMap.get(to);
    if (!entry || now - entry.windowStart > RATE_LIMIT_WINDOW_MS) {
        rateLimitMap.set(to, { count: 1, windowStart: now });
        return false;
    }
    if (entry.count >= RATE_LIMIT_MAX) {
        return true;
    }
    entry.count++;
    return false;
}

// Clean stale rate-limit entries every 5 minutes to avoid memory growth
setInterval(() => {
    const cutoff = Date.now() - RATE_LIMIT_WINDOW_MS;
    for (const [key, val] of rateLimitMap.entries()) {
        if (val.windowStart < cutoff) rateLimitMap.delete(key);
    }
}, 5 * 60 * 1000);

// ─────────────────────────────────────────────────────────────────────────────
// MEMORY WATCHDOG  – hard-restart if heap exceeds HEAP_LIMIT_MB
// ─────────────────────────────────────────────────────────────────────────────
function startMemoryWatchdog() {
    setInterval(() => {
        const heapUsedMB = process.memoryUsage().heapUsed / 1024 / 1024;
        const rss        = process.memoryUsage().rss       / 1024 / 1024;
        if (heapUsedMB > HEAP_LIMIT_MB) {
            console.error(
                `[WATCHDOG] Heap ${heapUsedMB.toFixed(1)} MB exceeded limit of ${HEAP_LIMIT_MB} MB. ` +
                `RSS: ${rss.toFixed(1)} MB. Triggering graceful exit for Docker restart…`
            );
            process.exit(1); // Docker restart: always
        } else {
            console.log(`[WATCHDOG] Heap: ${heapUsedMB.toFixed(1)} MB / ${HEAP_LIMIT_MB} MB | RSS: ${rss.toFixed(1)} MB`);
        }
    }, WATCHDOG_INTERVAL_MS);
}

// Watchdog de responsividade (Event Loop)
// Se o motor ficar travado (zumbi), ele se reinicia.
function startResponsivenessWatchdog() {
    let lastHeartbeat = Date.now();
    
    // Heartbeat interno
    setInterval(() => {
        lastHeartbeat = Date.now();
    }, 1000);

    // Verificador externo (não depende do loop travar para rodar o check)
    setInterval(() => {
        const diff = Date.now() - lastHeartbeat;
        if (diff > 60000) { // 60 segundos sem atualizar o pulso (tolerante ao boot)
            console.error(`[CRITICAL WATCHDOG] Event Loop bloqueado por ${diff}ms. Matando processo para auto-restart do Docker.`);
            process.exit(1);
        }
    }, 15000);
}

// ─────────────────────────────────────────────────────────────────────────────
// APP SETUP
// ─────────────────────────────────────────────────────────────────────────────
const app = express();
const port = 3000;

// Limit request body size to prevent payload bombs
app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ extended: false, limit: '2mb' }));

const redis = new Redis(process.env.REDIS_URL || 'redis://127.0.0.1:6379');

// Map to store multiple WhatsApp clients
const clients = new Map();
const qrCodes = new Map();
const connectionStatuses = new Map();
let isShuttingDown = false;
let isInitializingGlobal = false;

// ─────────────────────────────────────────────────────────────────────────────
// WHATSAPP INIT
// ─────────────────────────────────────────────────────────────────────────────
async function initWhatsApp(sessionName) {
    if (clients.has(sessionName)) {
        console.log(`[BOOT] Session ${sessionName} already exists.`);
        return;
    }

    // Global Lock: Evita que múltiplos Chromiums iniciem simultaneamente,
    // o que causa picos de CPU e falhas no handshake do QR Code.
    // Reduzido o intervalo de espera de 5s para 1s para maior agilidade.
    while (isInitializingGlobal) {
        console.log(`[BOOT] [${sessionName}] Another session is initializing, waiting...`);
        await new Promise(resolve => setTimeout(resolve, 1000));
    }

    let lockTimer;
    try {
        isInitializingGlobal = true;
        
        // Timer de segurança para liberar o lock se o wppconnect demorar demais (ex: esperando scan)
        lockTimer = setTimeout(() => {
            if (isInitializingGlobal) {
                console.log(`[BOOT] [${sessionName}] Lock timeout reached, releasing for next session.`);
                isInitializingGlobal = false;
            }
        }, 30000);

        connectionStatuses.set(sessionName, 'connecting');
        console.log(`[BOOT] Initializing session: ${sessionName}`);

        const sessionPath = path.join(__dirname, 'tokens', sessionName);
        
        if (fs.existsSync(sessionPath)) {
            try {
                const files = fs.readdirSync(sessionPath);
                files.forEach(file => {
                    if (file.startsWith('Singleton')) {
                        try {
                            fs.unlinkSync(path.join(sessionPath, file));
                            console.log(`[BOOT] [${sessionName}] Removed stale lock: ${file}`);
                        } catch (e) {}
                    }
                });
            } catch (e) {}
        }

        const client = await wppconnect.create({
            session: sessionName,
            catchQR: (base64Qr) => {
                qrCodes.set(sessionName, base64Qr);
                connectionStatuses.set(sessionName, 'qr_ready');
                console.log(`[${sessionName}] QR Code updated`);
            },
            statusFind: (status) => {
                let cleanStatus = status.toLowerCase();
                
                // Normalização: Se não está logado, para a UI é "pronto para scan"
                if (cleanStatus === 'notlogged' || cleanStatus === 'desconnectedmobile') {
                    cleanStatus = 'qr_ready';
                }
                
                connectionStatuses.set(sessionName, cleanStatus);
                console.log(`[${sessionName}] Status updated:`, cleanStatus);
                notifyLaravelStatus(sessionName, cleanStatus);
            },
            headless: 'new', // Use newer headless mode for better performance
            useChrome: false,
            executablePath: '/usr/bin/chromium',
            protocolTimeout: 60000, 
            sessionTokenPath: path.join(__dirname, 'tokens'),
            disableWelcome: true, // Speed up startup
            updatesLog: false,
            waitForLogin: false, // LIBERA O LOCK RÁPIDO: Não espera logar para retornar o client
            puppeteerOptions: {
                userDataDir: sessionPath,
                dumpio: false,
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--disable-extensions',
                    '--disable-web-security',
                    '--no-first-run',
                    '--no-default-browser-check',
                    '--disable-software-rasterizer',
                    '--disable-canvas-aa',
                    '--disable-2d-canvas-clip-aa',
                    '--disable-gl-drawing-for-tests',
                    '--js-flags=--max-old-space-size=1024',
                    '--remote-debugging-port=0',
                    '--disable-infobars',
                    '--no-pings',
                    '--disable-notifications',
                    '--disable-background-networking',
                    '--disable-default-apps',
                    '--disable-sync',
                    '--metrics-recording-only',
                    '--proxy-server="direct://"',
                    '--proxy-bypass-list=*',
                    '--no-zygote',
                    '--mute-audio',
                    '--disable-background-timer-throttling',
                    '--disable-backgrounding-occluded-windows',
                    '--disable-breakpad',
                    '--disable-component-update',
                    '--disable-domain-reliability',
                    '--disable-translate',
                    '--hide-scrollbars'
                ]
            },
            autoClose: false
        });

        // LIBERA O LOCK AGORA: O navegador já abriu, a CPU já "respirou".
        // O restante (esperar scan) não consome CPU pesada.
        isInitializingGlobal = false;
        if (typeof lockTimer !== 'undefined') clearTimeout(lockTimer);

        clients.set(sessionName, client);
        console.log(`[${sessionName}] WhatsApp Client Ready (Waiting for Scan or Connected)!`);
        startWorker(sessionName);
    } catch (err) {
        console.error(`[${sessionName}] Error creating client:`, err.message);
        connectionStatuses.set(sessionName, 'failed');
        
        // Se deu erro, precisamos garantir que o lock foi liberado
        isInitializingGlobal = false;
        if (typeof lockTimer !== 'undefined') clearTimeout(lockTimer);

        // Retry logic for stability: try again in 30s once
        setTimeout(() => {
            if (!clients.has(sessionName)) {
                console.log(`[${sessionName}] Retrying initialization...`);
                initWhatsApp(sessionName).catch(e => console.error(`[${sessionName}] Retry failed:`, e.message));
            }
        }, 30000);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// BOOTUP: LOAD ALL EXISTING SESSIONS
// ─────────────────────────────────────────────────────────────────────────────
async function loadExistingSessions() {
    const tokensPath = path.join(__dirname, 'tokens');

    if (!fs.existsSync(tokensPath)) {
        fs.mkdirSync(tokensPath, { recursive: true });
        return;
    }

    const entries = fs.readdirSync(tokensPath, { withFileTypes: true });
    const sessions = entries
        .filter(dirent => dirent.isDirectory())
        .map(dirent => dirent.name);

    console.log(`[BOOT] Found ${sessions.length} existing sessions to load.`);

    for (const session of sessions) {
        // Ignora a sessão master pois ela será iniciada manualmente ao final
        if (session === 'mensageria-tech') continue;
        
        // Carrega a sessão e AGUARDA ela iniciar completamente (ou tenta)
        // para não sobrecarregar a CPU e memória explodindo o Event Loop
        try {
            await initWhatsApp(session);
        } catch (e) {
            console.error(`[BOOT] Failed to load ${session}:`, e.message);
        }
        
        // Delay extra de segurança reduzido para 2s para acelerar o boot total
        await new Promise(resolve => setTimeout(resolve, 2000));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// OVERALL SESSION WATCHDOG
// ─────────────────────────────────────────────────────────────────────────────
function startSessionWatchdog() {
    setInterval(async () => {
        for (const [name, client] of clients.entries()) {
            try {
                const status = connectionStatuses.get(name);
                const restartableStates = [
                    'disconnected', 'closed', 'browserclose', 'failed', 
                    'qrreaderror', 'autoclose', 'offline', 'error'
                ];

                if (restartableStates.includes(status)) {
                    console.log(`[WATCHDOG] Session ${name} is in state: ${status}. Attempting recovery restart.`);
                    clients.delete(name);
                    qrCodes.delete(name);
                    initWhatsApp(name);
                }
            } catch (e) {
                console.warn(`[WATCHDOG] Failed to verify session ${name}:`, e.message);
            }
        }
    }, 60000); // verify every minute
}

// ─────────────────────────────────────────────────────────────────────────────
// QUEUE PROCESSOR
// ─────────────────────────────────────────────────────────────────────────────
const activeWorkers = new Set();

async function startWorker(sessionName) {
    if (activeWorkers.has(sessionName)) return;
    activeWorkers.add(sessionName);
    
    console.log(`[WORKER] [${sessionName}] Started.`);
    const sessionKey = `wpp_messages:${sessionName}`;

    while (!isShuttingDown) {
        try {
            const client = clients.get(sessionName);
            if (!client) {
                console.log(`[WORKER] [${sessionName}] Client lost. Stopping worker.`);
                activeWorkers.delete(sessionName);
                break;
            }

            console.log(`[WORKER] Checking queue for: ${sessionName} (${sessionKey})`);
            const data = await redis.blpop(sessionKey, 10); // 10s wait
            if (!data) continue;

            const message = JSON.parse(data[1]);
            const rawTo   = message.to || '';
            
            // Check Business Hours Schedule
            if (message.schedule_type === 'business_hours' && !isBusinessHours()) {
                console.log(`[WORKER] [${sessionName}] Outside business hours. Re-queuing and sleeping 1 min.`);
                await redis.rpush(sessionKey, data[1]); // Put it back
                await new Promise(resolve => setTimeout(resolve, 60000)); // Wait 1 min
                continue;
            }

            console.log(`[WORKER] [${sessionName}] Sending message to: ${rawTo}`);

            if (isRateLimited(rawTo)) {
                await notifyLaravel(message.log_id, 'failed', 'Rate limit exceeded');
                continue;
            }

            try {
                let to = rawTo;
                if (!to.includes('@')) to = to + '@c.us';

                // Trata o erro 'No LID for user' tentando validar o número antes do envio
                try {
                    const profile = await client.checkNumberStatus(to);
                    if (profile && profile.numberExists && profile.id && profile.id._serialized) {
                        to = profile.id._serialized;
                    } else if (profile && !profile.numberExists) {
                        throw new Error("O destinatário não possui WhatsApp cadastrado.");
                    }
                } catch (checkErr) {
                    console.log(`[WORKER] [${sessionName}] checkNumberStatus falhou para ${to}: ${checkErr.message || checkErr}`);
                }

                let mediaPath = message.media;
                let isTempFile = false;

                // Se for Base64, salva temporariamente para não estourar a RAM do Puppeteer
                if (typeof message.media === 'string' && message.media.startsWith('data:')) {
                    try {
                        const matches = message.media.match(/^data:([A-Za-z-+\/]+);base64,([\s\S]+)$/);
                        if (matches && matches.length === 3) {
                            const buffer = Buffer.from(matches[2].replace(/\s/g, ''), 'base64');
                            const tempDir = path.join(__dirname, 'temp_media');
                            if (!fs.existsSync(tempDir)) {
                                try { fs.mkdirSync(tempDir, { recursive: true }); } catch (e) {}
                            }
                            
                            const ext = matches[1].split('/')[1] || 'bin';
                            mediaPath = path.join(tempDir, `temp_${Date.now()}_${sessionName}.${ext}`);
                            fs.writeFileSync(mediaPath, buffer);
                            isTempFile = true;
                            console.log(`[WORKER] [${sessionName}] Base64 saved to temp file: ${mediaPath}`);
                        }
                    } catch (base64Err) {
                        console.error(`[WORKER] [${sessionName}] Error handling base64:`, base64Err.message);
                    }
                }

                const sendOp = mediaPath
                    ? client.sendFile(to, mediaPath, 'file', message.message)
                    : client.sendText(to, message.message);

                await Promise.race([
                    sendOp,
                    new Promise((_, reject) => setTimeout(() => reject(new Error('Send timeout')), 60000))
                ]);

                // Limpeza imediata de arquivo temporário
                if (isTempFile && fs.existsSync(mediaPath)) {
                    try { fs.unlinkSync(mediaPath); } catch (e) {}
                }

                await notifyLaravel(message.log_id, 'sent');
            } catch (error) {
                console.error(`[WORKER] [${sessionName}] Error:`, error.message);

                const isDefinitiveError = error.message && (
                    error.message.includes('destinatário não possui WhatsApp') ||
                    error.message.includes('No LID for user') ||
                    error.message.includes('not exists') ||
                    error.message.includes('Rate limit') ||
                    error.message.includes('invalid')
                );

                const maxRetries = 3;
                message.retries = (message.retries || 0) + 1;

                if (!isDefinitiveError && message.retries <= maxRetries) {
                    console.log(`[WORKER] [${sessionName}] Erro de sistema. Recolocando no fim da fila (Tentativa ${message.retries} de ${maxRetries}): ${error.message}`);
                    await redis.rpush(sessionKey, JSON.stringify(message));
                } else {
                    console.log(`[WORKER] [${sessionName}] Falha definitiva ou limite de tentativas retries atingido. Notificando Laravel: ${error.message}`);
                    await notifyLaravel(message.log_id, 'failed', error.message);
                }
            }

            // Cooldown per-session: 15 seconds (safe & reliable)
            const nextSend = Math.floor(Date.now() / 1000) + 15;
            await redis.set(`wpp_worker:next_send:${sessionName}`, nextSend, 'EX', 60);
            
            console.log(`[WORKER] [${sessionName}] Waiting 15s before next message... (Next at: ${nextSend})`);
            await new Promise(resolve => setTimeout(resolve, 15000));

        } catch (e) {
            console.error(`[WORKER] [${sessionName}] Loop error:`, e.message);
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
    activeWorkers.delete(sessionName);
}

// ─────────────────────────────────────────────────────────────────────────────
// NOTIFY LARAVEL (with timeout)
// ─────────────────────────────────────────────────────────────────────────────
async function notifyLaravel(logId, status, error = null) {
    if (!logId) return;
    try {
        const url = (process.env.WEBHOOK_URL || 'http://app/api/v1/webhook').replace(/\/$/, '') + '/status';
        await axios.post(url, {
            log_id: logId,
            status: status,
            error_message: error
        }, {
            timeout: AXIOS_TIMEOUT_MS,
            headers: { 'Authorization': 'Bearer ' + (process.env.INTERNAL_KEY || '7caeb868-3d08-4761-b126-4f601cd05f7a') }
        });
    } catch (err) {
        console.error('Failed to notify Laravel:', err.message);
    }
}

async function notifyLaravelStatus(session, status) {
    try {
        const url = (process.env.WEBHOOK_URL || 'http://app/api/v1/webhook').replace(/\/$/, '') + '/instance-status';
        await axios.post(url, {
            session: session,
            status: status
        }, {
            timeout: AXIOS_TIMEOUT_MS,
            headers: { 'Authorization': 'Bearer ' + (process.env.INTERNAL_KEY || '7caeb868-3d08-4761-b126-4f601cd05f7a') }
        });
    } catch (err) {
        console.error('Failed to notify Laravel status:', err.message);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ROUTES
// ─────────────────────────────────────────────────────────────────────────────
app.get('/qrcode/:session', (req, res) => {
    const session = req.params.session;
    const qr = qrCodes.get(session);
    const status = connectionStatuses.get(session);

    if (qr && status === 'qr_ready') {
        const base64Data = qr.replace(/^data:image\/png;base64,/, "");
        const img = Buffer.from(base64Data, 'base64');
        res.writeHead(200, {
            'Content-Type': 'image/png',
            'Content-Length': img.length
        });
        res.end(img);
    } else {
        res.status(404).json({ status: 'not_available', sessionStatus: status });
    }
});

app.get('/status/:session', (req, res) => {
    const session = req.params.session;
    let status = (connectionStatuses.get(session) || 'offline').toString().toLowerCase();

    if (['islogged', 'logged', 'authenticated', 'main', 'syncing'].includes(status)) {
        status = 'connected';
    }
    if (status.includes('qr')) {
        status = 'qr_ready';
    }

    res.json({
        status,
        session,
        has_client: clients.has(session),
        timestamp: new Date().toISOString()
    });
});

app.post('/start/:session', async (req, res) => {
    const session = req.params.session;
    if (clients.has(session)) {
        return res.json({ status: 'already_running', session });
    }
    initWhatsApp(session); // async but we don't await full readiness
    res.json({ status: 'initializing', session });
});

app.post('/logout/:session', async (req, res) => {
    const session = req.params.session;
    const client = clients.get(session);
    
    console.log(`[${session}] Logout request received`);
    
    if (client) {
        try {
            // Tenta logout mas não trava se o browser já estiver morto
            await Promise.race([
                client.logout(),
                new Promise((_, reject) => setTimeout(() => reject(new Error('Logout timeout')), 5000))
            ]).catch(e => console.warn(`[${session}] Logout promise failed (expected if browser is dead):`, e.message));
            
            console.log(`[${session}] Session cleanup initiated`);
        } catch (e) {
            console.error(`[${session}] Logout error details:`, e.message);
        } finally {
            // SEMPRE limpa do mapa local, independente de sucesso no logout remoto
            clients.delete(session);
            qrCodes.delete(session);
            connectionStatuses.set(session, 'disconnected');
            res.json({ status: 'logged_out', session });
        }
    } else {
        connectionStatuses.set(session, 'disconnected');
        qrCodes.delete(session);
        res.json({ status: 'not_connected', session });
    }
});

// Health endpoint for Docker HEALTHCHECK
app.get('/health', async (req, res) => {
    const heapMB  = (process.memoryUsage().heapUsed / 1024 / 1024).toFixed(1);
    const rssMB   = (process.memoryUsage().rss       / 1024 / 1024).toFixed(1);
    
    // Simplificado: removemos a chamada ao Redis que pode travar se o event loop estiver ocupado
    // ou se houver problemas de rede interna, focando no estado do processo Node
    const ok = !isShuttingDown && parseFloat(heapMB) < HEAP_LIMIT_MB;
    
    res.status(ok ? 200 : 503).json({
        ok,
        heap_mb:        parseFloat(heapMB),
        heap_limit_mb:  HEAP_LIMIT_MB,
        rss_mb:         parseFloat(rssMB),
        connection:     connectionStatuses.get('mensageria-tech') || 'offline',
        uptime_s:       Math.floor(process.uptime()),
        shuttingDown:   isShuttingDown,
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// GRACEFUL SHUTDOWN
// ─────────────────────────────────────────────────────────────────────────────
function gracefulShutdown(signal) {
    console.log(`[SHUTDOWN] Received ${signal}. Shutting down gracefully…`);
    isShuttingDown = true;
    redis.disconnect();
    for (const [name, client] of clients.entries()) {
        try { client.close(); } catch (_) {}
    }
    setTimeout(() => process.exit(0), 3000);
}

process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
process.on('SIGINT',  () => gracefulShutdown('SIGINT'));

// Catch uncaught exceptions to avoid silent crashes
process.on('uncaughtException', (err) => {
    console.error('[UNCAUGHT EXCEPTION]', err);
    // Do NOT exit – let Docker restart policy handle severe cases via watchdog
});
process.on('unhandledRejection', (reason) => {
    console.error('[UNHANDLED REJECTION]', reason);
});

// ─────────────────────────────────────────────────────────────────────────────
// START
// ─────────────────────────────────────────────────────────────────────────────
app.listen(port, '0.0.0.0', () => {
    console.log(`Bridge HTTP server running on port ${port}`);
    startMemoryWatchdog();
    startResponsivenessWatchdog(); // Monitora se o motor ficou zumbi
    startSessionWatchdog(); // Extra slack: monitor all sessions
    loadExistingSessions(); // Auto-load all previous sessions
});

// For backward compatibility or master session
initWhatsApp('mensageria-tech');
