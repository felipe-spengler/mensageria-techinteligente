import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Checking token folder...');
  const cmd = `
    ls -la /data/coolify/applications/wsgc44okcckccwws4ss4kcww/bridge/tokens/client_3
    echo "Checking client_1..."
    ls -la /data/coolify/applications/wsgc44okcckccwws4ss4kcww/bridge/tokens/client_1
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
