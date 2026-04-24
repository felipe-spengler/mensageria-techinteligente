import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Diagnosing client_3...');
  const cmd = `
    echo "=== TOKEN FOLDER ==="
    ls -la /data/coolify/applications/wsgc44okcckccwws4ss4kcww/bridge/tokens/client_3 || echo "Folder not found"
    
    echo ""
    echo "=== BRIDGE STATUS VIA CURL ==="
    docker exec $(docker ps -q --filter name=bridge-wsgc44) curl -s http://localhost:3000/status/client_3
    
    echo ""
    echo "=== BRIDGE LOGS (STATUS UPDATES) ==="
    docker logs $(docker ps -q --filter name=bridge-wsgc44) --tail 2000 | grep -i "client_3" | grep -i "status" | tail -n 20
    
    echo ""
    echo "=== BRIDGE LOGS (ERROR/QR) ==="
    docker logs $(docker ps -q --filter name=bridge-wsgc44) --tail 2000 | grep -i "client_3" | grep -E "Error|QR" | tail -n 20
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
