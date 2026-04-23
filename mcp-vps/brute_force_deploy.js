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
  const dir = process.env.VPS_COMPOSE_DIR;
  // Brute force: prune again, then build and start manually with high priority
  const cmd = `
    cd ${dir}
    echo "Current directory: $(pwd)"
    echo "Resetting Docker again to be sure..."
    docker compose down --volumes --remove-orphans
    docker system prune -f
    echo "Starting manual build and up..."
    # We use --build to force a rebuild of everything with the new optimizations
    docker compose up --build -d
    echo "Manual deployment triggered. Check status with docker ps."
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
