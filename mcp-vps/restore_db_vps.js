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
  // Restore: switch back to the old db-data volume
  const cmd = `
    cd ${dir}
    echo "Stopping containers..."
    docker compose down
    echo "Swapping volume in docker-compose.yaml..."
    # Replace v2 with the old one
    sed -i 's/wsgc44okcckccwws4ss4kcww_db-data-v2/wsgc44okcckccwws4ss4kcww_db-data/g' docker-compose.yaml
    echo "Starting containers with OLD database..."
    docker compose up -d
    echo "Restore attempt complete. Please check the users now."
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
