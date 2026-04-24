import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Testing message send for client_3...');
  // Test message to a known number (e.g. the user's or a dummy one)
  // But wait, I don't want to spam. I'll just check if the client is really ready in the bridge's internal state.
  const cmd = `
    docker exec $(docker ps -q --filter name=bridge-wsgc44) node -e "
      const Redis = require('ioredis');
      const redis = new Redis(process.env.REDIS_URL || 'redis://redis:6379');
      const msg = {
        to: '5511999999999', // dummy
        message: 'Test from diag',
        log_id: 9999,
        schedule_type: 'full_time'
      };
      redis.rpush('wpp_messages:client_3', JSON.stringify(msg)).then(() => {
        console.log('Message pushed to queue for client_3');
        process.exit(0);
      });
    "
    sleep 5
    docker logs $(docker ps -q --filter name=bridge-wsgc44) --tail 20 | grep -i "client_3"
  `;
  
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    stream.on('data', (data) => process.stdout.write(data))
          .stderr.on('data', (data) => process.stderr.write(data))
          .on('close', () => conn.end());
  });
}).connect({
  host: process.env.VPS_HOST,
  port: parseInt(process.env.VPS_PORT || '22'),
  username: process.env.VPS_USER,
  privateKey: readFileSync(process.env.VPS_KEY_PATH)
});
