import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Verifying user 3 / client 3...');
  const cmd = `
    echo "=== DATABASE INSTANCES ==="
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -uapp_user -psupersenha -e "USE mensageria; SELECT * FROM whatsapp_instances;"
    
    echo ""
    echo "=== BRIDGE LOGS FOR client_3 ==="
    docker logs $(docker ps -q --filter name=bridge-wsgc44) --tail 1000 | grep -i "client_3" | tail -n 20
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
