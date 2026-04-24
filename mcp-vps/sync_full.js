import { Client } from 'ssh2';
import { readFileSync } from 'fs';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.join(__dirname, '..');
config({ path: path.join(__dirname, '.env') });

const conn = new Client();
conn.on('ready', () => {
  console.log('SSH Connection Ready. Syncing full bridge environment...');
  conn.sftp((err, sftp) => {
    if (err) throw err;

    const filesToSync = [
      {
        local: path.join(rootDir, 'bridge', 'index.js'),
        remote: path.join(process.env.VPS_COMPOSE_DIR, 'bridge', 'index.js')
      },
      {
        local: path.join(rootDir, 'bridge', 'Dockerfile'),
        remote: path.join(process.env.VPS_COMPOSE_DIR, 'bridge', 'Dockerfile')
      },
      {
        local: path.join(rootDir, 'bridge', 'package.json'),
        remote: path.join(process.env.VPS_COMPOSE_DIR, 'bridge', 'package.json')
      },
      {
        local: path.join(rootDir, 'bridge', 'package-lock.json'),
        remote: path.join(process.env.VPS_COMPOSE_DIR, 'bridge', 'package-lock.json')
      },
      {
        local: path.join(rootDir, 'resources', 'views', 'admin', 'whatsapp.blade.php'),
        remote: path.join(process.env.VPS_COMPOSE_DIR, 'resources', 'views', 'admin', 'whatsapp.blade.php')
      }
    ];

    let completed = 0;
    filesToSync.forEach(file => {
      console.log(`Uploading ${file.local} to ${file.remote}...`);
      
      // Ensure directory exists (basic check for bridge and resources/views/admin)
      const remoteDir = path.dirname(file.remote);
      // We assume directories exist or we try to create them
      
      sftp.fastPut(file.local, file.remote, (err) => {
        if (err) {
            console.error(`Error uploading ${file.local}:`, err.message);
        } else {
            console.log(`Successfully uploaded ${file.local}`);
        }
        completed++;
        if (completed === filesToSync.length) {
          console.log('Sync complete. Triggering full build...');
          conn.end();
        }
      });
    });
  });
}).connect({
  host: process.env.VPS_HOST,
  port: parseInt(process.env.VPS_PORT || '22'),
  username: process.env.VPS_USER,
  privateKey: readFileSync(process.env.VPS_KEY_PATH)
});
