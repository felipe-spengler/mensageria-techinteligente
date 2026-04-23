import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Checking user 3 status...');
  const cmd = `
    echo "=== API KEYS ==="
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -uapp_user -psupersenha -e "USE mensageria; SELECT id, user_id, plan_id, status FROM api_keys;"
    
    echo ""
    echo "=== PIX TRANSACTIONS ==="
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -uapp_user -psupersenha -e "USE mensageria; SELECT id, user_id, status FROM pix_transactions;"
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
