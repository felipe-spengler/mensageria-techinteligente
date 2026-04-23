import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Nuking client_3 session for a fresh start...');
  
  // 1. Force the bridge to "forget" the session if it's running (by calling /start again or just killing container if needed)
  // 2. Delete the token folder on the VPS
  // 3. The bridge will auto-restart it or wait for a new /start
  const cmd = `
    # Delete the profile folder to ensure a clean start
    sudo rm -rf /data/coolify/applications/wsgc44okcckccwws4ss4kcww/bridge/tokens/client_3
    echo "Profile folder for client_3 deleted."
    
    # Restart the bridge container to clear in-memory state
    docker restart $(docker ps -q --filter name=bridge-wsgc44)
    echo "Bridge container restarted."
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
