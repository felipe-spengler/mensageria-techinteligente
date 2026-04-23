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
  
  // SURGERY:
  // 1. Start DB with skip-grant-tables
  // 2. Reset root password
  // 3. Restart DB normally
  const cmd = `
    cd ${dir}
    echo "Stopping DB..."
    docker compose stop db
    
    echo "Starting DB in maintenance mode..."
    # We modify the command temporarily to skip permissions
    sed -i 's/max_connections/skip-grant-tables --max_connections/' docker-compose.yaml
    docker compose up -d db
    
    sleep 10
    
    echo "Resetting root password..."
    docker exec $(docker ps -q --filter name=db-wsgc44) mysql -e "FLUSH PRIVILEGES; ALTER USER 'root'@'%' IDENTIFIED BY 'supersenha'; ALTER USER 'root'@'localhost' IDENTIFIED BY 'supersenha'; FLUSH PRIVILEGES;"
    
    echo "Stopping maintenance mode..."
    docker compose stop db
    sed -i 's/--skip-grant-tables //' docker-compose.yaml
    
    echo "Restarting everything..."
    docker compose up -d
    echo "Surgery complete. Data should be accessible now."
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
