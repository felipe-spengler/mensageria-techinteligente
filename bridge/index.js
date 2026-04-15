const wppconnect = require('@wppconnect-team/wppconnect');
const Redis = require('ioredis');
const axios = require('axios');
const express = require('express');
require('dotenv').config();

// ─────────────────────────────────────────────────────────────────────────────
// PROTECTION CONFIG
// ─────────────────────────────────────────────────────────────────────────────
const HEAP_LIMIT_MB        = parseInt(process.env.HEAP_LIMIT_MB        || '1100');  // restart threshold (MB)
const WATCHDOG_INTERVAL_MS = parseInt(process.env.WATCHDOG_INTERVAL_MS || '30000'); // check every 30s
const AXIOS_TIMEOUT_MS     = parseInt(process.env.AXIOS_TIMEOUT_MS     || '30000'); // 30s per webhook call
const QUEUE_MAX_SIZE       = parseInt(process.env.QUEUE_MAX_SIZE       || '5000');  // max Redis queue depth
const RATE_LIMIT_MAX       = parseInt(process.env.RATE_LIMIT_MAX       || '5');     // max msgs per number/min
const RATE_LIMIT_WINDOW_MS = parseInt(process.env.RATE_LIMIT_WINDOW_MS || '60000'); // 1 min window

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

// ─────────────────────────────────────────────────────────────────────────────
// APP SETUP
// ─────────────────────────────────────────────────────────────────────────────
const app = express();
const port = 3000;

// Limit request body size to prevent payload bombs
app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ extended: false, limit: '2mb' }));

const redis = new Redis(process.env.REDIS_URL || 'redis://127.0.0.1:6379');

let whatsappClient = null;
let currentQRCode = null;
let connectionStatus = 'initializing';
let isShuttingDown = false;

// ─────────────────────────────────────────────────────────────────────────────
// WHATSAPP INIT
// ─────────────────────────────────────────────────────────────────────────────
async function initWhatsApp() {
    connectionStatus = 'connecting';

    // Remove stale Chromium profile locks left by previous container crashes.
    // Without this, Chromium refuses to start with "profile in use" error (Code 21).
    const fs = require('fs');
    const path = require('path');
    const sessionPath = path.join(__dirname, 'tokens', 'mensageria-tech');
    
    if (fs.existsSync(sessionPath)) {
        const files = fs.readdirSync(sessionPath);
        files.forEach(file => {
            if (file.startsWith('Singleton')) {
                try {
                    fs.unlinkSync(path.join(sessionPath, file));
                    console.log(`[BOOT] Removed stale lock: ${file}`);
                } catch (e) {
                    console.warn(`[BOOT] Could not remove ${file}:`, e.message);
                }
            }
        });
    }

    whatsappClient = await wppconnect.create({
        session: 'mensageria-tech',
        catchQR: (base64Qr) => {
            currentQRCode = base64Qr;
            connectionStatus = 'qr_ready';
            console.log('QR Code updated');
        },
        statusFind: (status) => {
            connectionStatus = status;
            console.log('Status updated:', status);
        },
        headless: true,
        useChrome: true,
        sessionTokenPath: './tokens',
        puppeteerOptions: {
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-extensions',
                '--js-flags=--max-old-space-size=768', // restrict Chromium JS heap
            ]
        },
        autoClose: false
    });

    console.log('WhatsApp Client Ready!');
    startMemoryWatchdog();
    processQueue();
}

// ─────────────────────────────────────────────────────────────────────────────
// QUEUE PROCESSOR
// ─────────────────────────────────────────────────────────────────────────────
async function processQueue() {
    while (!isShuttingDown) {
        try {
            // Safety: abort if queue is dangerously large (backpressure)
            const queueLen = await redis.llen('wpp_messages');
            if (queueLen > QUEUE_MAX_SIZE) {
                console.warn(`[PROTECTION] Queue size ${queueLen} exceeds max ${QUEUE_MAX_SIZE}. Pausing 60s.`);
                await new Promise(resolve => setTimeout(resolve, 60000));
                continue;
            }

            const data = await redis.blpop('wpp_messages', 5); // 5s timeout (non-blocking)
            if (!data) continue;

            const message = JSON.parse(data[1]);
            const rawTo   = message.to || '';
            console.log('Processing message to:', rawTo);

            // Per-number rate limiting
            if (isRateLimited(rawTo)) {
                console.warn(`[RATE-LIMIT] Dropping message to ${rawTo} – limit ${RATE_LIMIT_MAX}/${RATE_LIMIT_WINDOW_MS}ms reached.`);
                await notifyLaravel(message.log_id, 'failed', 'Rate limit exceeded for this number');
                continue;
            }

            try {
                let to = rawTo;
                if (!to.includes('@')) to = to + '@c.us';

                // Timeout wrapper: 60s per send attempt
                const sendTimeout = new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Send timeout (60s)')), 60000)
                );

                const sendOp = message.media
                    ? whatsappClient.sendFile(to, message.media, 'file', message.message)
                    : whatsappClient.sendText(to, message.message);

                await Promise.race([sendOp, sendTimeout]);

                console.log('Message sent successfully. Starting cooldown of 120s.');
                await notifyLaravel(message.log_id, 'sent');
            } catch (error) {
                console.error('Error sending message:', error.message);
                await notifyLaravel(message.log_id, 'failed', error.message || 'Error sending');
            }

            // Anti-ban cooldown: 120 s
            await new Promise(resolve => setTimeout(resolve, 120000));

        } catch (e) {
            console.error('Queue processing error:', e.message);
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
    console.log('[SHUTDOWN] Queue processor stopped.');
}

// ─────────────────────────────────────────────────────────────────────────────
// NOTIFY LARAVEL (with timeout)
// ─────────────────────────────────────────────────────────────────────────────
async function notifyLaravel(logId, status, error = null) {
    if (!logId) return;
    
    try {
        let url = process.env.WEBHOOK_URL;
        if (!url || url === 'undefined') {
            url = 'http://app/api/v1/webhook';
        }
        
        const target = url.replace(/\/$/, '') + '/status';
        console.log('Notifying Laravel status:', status, 'at', target);

        await axios.post(target, {
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

// ─────────────────────────────────────────────────────────────────────────────
// ROUTES
// ─────────────────────────────────────────────────────────────────────────────
app.get('/qrcode', (req, res) => {
    if (currentQRCode && connectionStatus === 'qr_ready') {
        const base64Data = currentQRCode.replace(/^data:image\/png;base64,/, "");
        const img = Buffer.from(base64Data, 'base64');
        res.writeHead(200, {
            'Content-Type': 'image/png',
            'Content-Length': img.length
        });
        res.end(img);
    } else {
        res.status(404).json({ status: 'not_available', connection: connectionStatus });
    }
});

app.get('/status', (req, res) => {
    let status = (connectionStatus || '').toString().toLowerCase();

    if (['islogged', 'logged', 'authenticated', 'main', 'syncing'].includes(status)) {
        status = 'connected';
    }
    if (status.includes('qr')) {
        status = status.replace(/[^a-z0-9_]/g, '');
    }
    if (['disconnected', 'disconnecting', 'failed'].includes(status)) {
        status = 'disconnected';
    }

    res.json({
        status,
        has_client: !!whatsappClient,
        connectionStatus: connectionStatus,
        timestamp: new Date().toISOString()
    });
});

// Health endpoint for Docker HEALTHCHECK
app.get('/health', async (req, res) => {
    const heapMB  = (process.memoryUsage().heapUsed / 1024 / 1024).toFixed(1);
    const rssMB   = (process.memoryUsage().rss       / 1024 / 1024).toFixed(1);
    let   queueLen = 0;
    try { queueLen = await redis.llen('wpp_messages'); } catch (_) {}

    const ok = !isShuttingDown && parseFloat(heapMB) < HEAP_LIMIT_MB;
    res.status(ok ? 200 : 503).json({
        ok,
        heap_mb:        parseFloat(heapMB),
        heap_limit_mb:  HEAP_LIMIT_MB,
        rss_mb:         parseFloat(rssMB),
        queue_depth:    queueLen,
        queue_max:      QUEUE_MAX_SIZE,
        connection:     connectionStatus,
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
    if (whatsappClient) {
        try { whatsappClient.close(); } catch (_) {}
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
    console.log(`[PROTECTION] Heap limit: ${HEAP_LIMIT_MB} MB | Rate limit: ${RATE_LIMIT_MAX} msgs/${RATE_LIMIT_WINDOW_MS}ms | Queue max: ${QUEUE_MAX_SIZE}`);
});

initWhatsApp();
