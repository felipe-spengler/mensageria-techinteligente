const wppconnect = require('@wppconnect-team/wppconnect');
const Redis = require('ioredis');
const axios = require('axios');
require('dotenv').config();

const redis = new Redis(process.env.REDIS_URL || 'redis://127.0.0.1:6379');

async function start() {
    const client = await wppconnect.create({
        session: 'mensageria-tech',
        catchQR: (base64Qr, asciiQR) => {
            console.log('QR Code received');
        },
        headless: true,
        useChrome: true,
        puppeteerOptions: {
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        },
        autoClose: false
    });

    console.log('WhatsApp Bridge Started!');

    while (true) {
        const data = await redis.blpop('wpp_messages', 0);
        if (data) {
            const message = JSON.parse(data[1]);
            console.log('Processing message to:', message.to);

            try {
                if (message.media) {
                    await client.sendImage(
                        message.to + '@c.us',
                        message.media,
                        'image-name',
                        message.message
                    );
                } else {
                    await client.sendText(message.to + '@c.us', message.message);
                }
                
                console.log('Message sent successfully. Waiting 2 minutes before next one...');
                
                // Notify Laravel
                await axios.post(process.env.WEBHOOK_URL + '/status', {
                    log_id: message.log_id,
                    status: 'sent'
                }, {
                    headers: { 'Authorization': 'Bearer ' + (process.env.API_KEY || 'dummy-key') }
                });

            } catch (error) {
                console.error('Error sending message:', error);
                
                // Notify Laravel of failure
                await axios.post(process.env.WEBHOOK_URL + '/status', {
                    log_id: message.log_id,
                    status: 'failed',
                    error_message: error.message
                }, {
                    headers: { 'Authorization': 'Bearer ' + (process.env.API_KEY || 'dummy-key') }
                });
            }

            // Aquecimento: Aguarda 2 minutos (120000 ms)
            await new Promise(resolve => setTimeout(resolve, 120000));
        }
    }
}

start();
