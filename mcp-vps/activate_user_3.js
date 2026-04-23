import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Activating user 3...');
  const cmd = `
    echo "=== TRANSFERRING API KEY TO USER 3 ==="
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -uapp_user -psupersenha -e "USE mensageria; UPDATE api_keys SET user_id = 3 WHERE id = 2;"
    
    echo "=== CANCELING PENDING TRANSACTIONS FOR USER 3 ==="
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -uapp_user -psupersenha -e "USE mensageria; UPDATE pix_transactions SET status = 'canceled' WHERE user_id = 3 AND status = 'pending';"
    
    echo "=== VERIFYING STATUS ==="
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -uapp_user -psupersenha -e "USE mensageria; SELECT id, user_id, status FROM api_keys WHERE user_id = 3;"
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
