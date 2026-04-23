import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Checking old database contents...');
  // Query to check databases, tables, and users directly via mysql client
  const cmd = `
    DB_CONTAINER=$(docker ps -q --filter name=db-wsgc44)
    if [ -z "$DB_CONTAINER" ]; then
      echo "Error: Could not find DB container."
      exit 1
    fi
    echo "--- DATABASES ---"
    docker exec $DB_CONTAINER mysql -uroot -psupersenha -e "SHOW DATABASES;"
    echo "--- TABLES IN MENSAGERIA ---"
    docker exec $DB_CONTAINER mysql -uroot -psupersenha -e "USE mensageria; SHOW TABLES;"
    echo "--- USERS COUNT ---"
    docker exec $DB_CONTAINER mysql -uroot -psupersenha -e "USE mensageria; SELECT COUNT(*) as Total_Users FROM users;"
    echo "--- FIRST 5 USERS ---"
    docker exec $DB_CONTAINER mysql -uroot -psupersenha -e "USE mensageria; SELECT id, name, email FROM users LIMIT 5;"
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
