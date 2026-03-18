const wppconnect = require('@wppconnect-team/wppconnect');
const Redis = require('ioredis');
const axios = require('axios');
const express = require('express');
require('dotenv').config();

const app = express();
const port = 3000;
const redis = new Redis(process.env.REDIS_URL || 'redis://127.0.0.1:6379');

let whatsappClient = null;
let currentQRCode = null;
let connectionStatus = 'initializing';

async function initWhatsApp() {
    connectionStatus = 'connecting';
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
        puppeteerOptions: {
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
        },
        autoClose: false
    });

    console.log('WhatsApp Client Ready!');
    processQueue();
}

async function processQueue() {
    while (true) {
        try {
            const data = await redis.blpop('wpp_messages', 0);
            if (data) {
                const message = JSON.parse(data[1]);
                console.log('Processing message to:', message.to);

                try {
                    if (message.media) {
                        await whatsappClient.sendImage(
                            message.to + '@c.us',
                            message.media,
                            'image-name',
                            message.message
                        );
                    } else {
                        await whatsappClient.sendText(message.to + '@c.us', message.message);
                    }
                    
                    console.log('Message sent. Waiting for 2 min cooldown.');
                    await notifyLaravel(message.log_id, 'sent');
                } catch (error) {
                    console.error('Error sending message:', error);
                    await notifyLaravel(message.log_id, 'failed', error.message);
                }

                // Cooldown: 2 min
                await new Promise(resolve => setTimeout(resolve, 120000));
            }
        } catch (e) {
            console.error('Queue processing error:', e);
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
}

async function notifyLaravel(logId, status, error = null) {
    try {
        await axios.post(process.env.WEBHOOK_URL + '/status', {
            log_id: logId,
            status: status,
            error_message: error
        }, {
            headers: { 'Authorization': 'Bearer ' + (process.env.INTERNAL_KEY || '7caeb868-3d08-4761-b126-4f601cd05f7a') }
        });
    } catch (err) {
        console.error('Failed to notify Laravel:', err.message);
    }
}

// Routes
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

    if (status === 'islogged' || status === 'logged' || status === 'authenticated') {
        status = 'connected';
    }

    res.json({
        status,
        has_client: !!whatsappClient,
        timestamp: new Date().toISOString()
    });
});

app.listen(port, '0.0.0.0', () => {
    console.log(`Bridge HTTP server running on port ${port}`);
});

initWhatsApp();
