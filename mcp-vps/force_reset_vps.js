import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready');
  // 1. Stop all app containers
  // 2. Kill any active build helpers to clear the queue
  // 3. Prune everything
  const cmd = `
    echo "Stopping all containers for this app..."
    docker ps -q --filter name=${process.env.COOLIFY_APP_PREFIX} | xargs -r docker stop
    echo "Killing all build helpers..."
    docker ps -q --filter name=coolify-helper | xargs -r docker stop
    echo "Removing containers..."
    docker ps -aq --filter name=${process.env.COOLIFY_APP_PREFIX} | xargs -r docker rm
    docker ps -aq --filter name=coolify-helper | xargs -r docker rm
    echo "Pruning system..."
    docker system prune -af
    echo "VPS Reset complete. Ready for a clean deployment."
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
