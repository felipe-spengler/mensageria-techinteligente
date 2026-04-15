import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  McpError,
  ErrorCode,
} from '@modelcontextprotocol/sdk/types.js';
import { Client } from 'ssh2';
import { readFileSync, existsSync } from 'fs';
import { createRequire } from 'module';
import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
config({ path: path.join(__dirname, '.env') });

// ─────────────────────────────────────────────────────────────────────────────
// SSH CONFIG  (preencha .env ou variáveis de ambiente)
// ─────────────────────────────────────────────────────────────────────────────
const SSH_HOST     = process.env.VPS_HOST;
const SSH_PORT     = parseInt(process.env.VPS_PORT  || '22');
const SSH_USER     = process.env.VPS_USER  || 'root';
const SSH_KEY_PATH = process.env.VPS_KEY_PATH;   // ex: C:\Users\Felipe\.ssh\id_rsa
const SSH_PASSWORD = process.env.VPS_PASSWORD;   // alternativa à chave
const COMPOSE_DIR  = process.env.VPS_COMPOSE_DIR || '/root/saas-whatsapp'; // pasta do docker-compose na VPS

// Serviços conhecidos (nomes no docker-compose.yaml do projeto)
const KNOWN_SERVICES = ['app', 'worker', 'bridge', 'db', 'redis', 'phpmyadmin'];

// Prefixo Coolify para filtrar stats (ex: app-wsgc44...)
const COOLIFY_PREFIX = process.env.COOLIFY_APP_PREFIX || 'wsgc44okcckccwws4ss4kcww';

// ─────────────────────────────────────────────────────────────────────────────
// SSH HELPER — executa um comando na VPS e retorna stdout+stderr
// ─────────────────────────────────────────────────────────────────────────────
function sshExec(command, timeoutMs = 30000) {
  return new Promise((resolve, reject) => {
    const conn = new Client();

    const authConfig = {
      host:    SSH_HOST,
      port:    SSH_PORT,
      username: SSH_USER,
    };

    if (SSH_KEY_PATH && existsSync(SSH_KEY_PATH)) {
      authConfig.privateKey = readFileSync(SSH_KEY_PATH);
    } else if (SSH_PASSWORD) {
      authConfig.password = SSH_PASSWORD;
    } else {
      reject(new Error('SSH: configure VPS_KEY_PATH ou VPS_PASSWORD no .env'));
      return;
    }

    const timer = setTimeout(() => {
      conn.end();
      reject(new Error(`SSH timeout (${timeoutMs}ms) executando: ${command}`));
    }, timeoutMs);

    conn.on('ready', () => {
      conn.exec(command, (err, stream) => {
        if (err) { clearTimeout(timer); conn.end(); reject(err); return; }

        let stdout = '';
        let stderr = '';

        stream.on('data', (d) => { stdout += d.toString(); });
        stream.stderr.on('data', (d) => { stderr += d.toString(); });

        stream.on('close', (code) => {
          clearTimeout(timer);
          conn.end();
          resolve({ stdout: stdout.trim(), stderr: stderr.trim(), code });
        });
      });
    });

    conn.on('error', (err) => { clearTimeout(timer); reject(err); });
    conn.connect(authConfig);
  });
}

// Helper: roda comando no diretório do docker-compose
function dc(cmd) {
  return sshExec(`cd ${COMPOSE_DIR} && ${cmd}`);
}

// ─────────────────────────────────────────────────────────────────────────────
// MCP SERVER
// ─────────────────────────────────────────────────────────────────────────────
const server = new Server(
  { name: 'mcp-vps-ssh', version: '1.0.0' },
  { capabilities: { tools: {} } }
);

// ── LISTA DE FERRAMENTAS ──────────────────────────────────────────────────────
server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'docker_ps',
      description: 'Lista todos os containers Docker na VPS com status, CPU e RAM.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'docker_logs',
      description: 'Lê os últimos N logs de um serviço Docker.',
      inputSchema: {
        type: 'object',
        properties: {
          service: { type: 'string', description: `Serviço: ${KNOWN_SERVICES.join(', ')}` },
          lines:   { type: 'number', description: 'Quantidade de linhas (default: 100)' },
        },
        required: ['service'],
      },
    },
    {
      name: 'docker_stats',
      description: 'Mostra uso atual de CPU e RAM de todos os containers.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'docker_restart',
      description: 'Reinicia um serviço específico sem derrubar os outros.',
      inputSchema: {
        type: 'object',
        properties: {
          service: { type: 'string', description: `Serviço: ${KNOWN_SERVICES.join(', ')}` },
        },
        required: ['service'],
      },
    },
    {
      name: 'docker_compose_up',
      description: 'Sobe/atualiza todos os serviços (docker compose up -d). Use após deploy.',
      inputSchema: {
        type: 'object',
        properties: {
          build: { type: 'boolean', description: 'Se true, faz --build antes de subir (default: false)' },
        },
        required: [],
      },
    },
    {
      name: 'docker_compose_down',
      description: 'Para todos os containers (docker compose down). Use com cuidado.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'read_file',
      description: 'Lê o conteúdo de qualquer arquivo na VPS.',
      inputSchema: {
        type: 'object',
        properties: {
          path: { type: 'string', description: 'Caminho absoluto do arquivo (ex: /root/saas-whatsapp/.env)' },
        },
        required: ['path'],
      },
    },
    {
      name: 'write_file',
      description: 'Cria ou sobrescreve um arquivo na VPS com o conteúdo fornecido.',
      inputSchema: {
        type: 'object',
        properties: {
          path:    { type: 'string', description: 'Caminho absoluto do arquivo' },
          content: { type: 'string', description: 'Conteúdo a escrever' },
        },
        required: ['path', 'content'],
      },
    },
    {
      name: 'shell',
      description: 'Executa um comando shell na VPS. Use para diagnósticos (df, free, top, etc.).',
      inputSchema: {
        type: 'object',
        properties: {
          command: { type: 'string', description: 'Comando a executar (ex: df -h, free -m)' },
        },
        required: ['command'],
      },
    },
    {
      name: 'health_check',
      description: 'Verifica saúde geral da VPS: disco, RAM, CPU e status dos containers.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
  ],
}));

// ── EXECUÇÃO DAS FERRAMENTAS ──────────────────────────────────────────────────
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  if (!SSH_HOST) {
    return { content: [{ type: 'text', text: '❌ VPS_HOST não configurado no .env do mcp-vps.' }] };
  }

  try {
    // ── docker_ps ──────────────────────────────────────────────────────────
    if (name === 'docker_ps') {
      const r = await dc('docker compose ps -a');
      return { content: [{ type: 'text', text: r.stdout || r.stderr || '(sem saída)' }] };
    }

    // ── docker_logs ────────────────────────────────────────────────────────
    if (name === 'docker_logs') {
      const service = args.service;
      if (!KNOWN_SERVICES.includes(service)) {
        return { content: [{ type: 'text', text: `❌ Serviço desconhecido: ${service}. Válidos: ${KNOWN_SERVICES.join(', ')}` }] };
      }
      const lines = args.lines || 100;
      const r = await dc(`docker compose logs --no-color --tail=${lines} ${service}`);
      return { content: [{ type: 'text', text: r.stdout || r.stderr || '(sem logs)' }] };
    }

    // ── docker_stats ───────────────────────────────────────────────────────
    if (name === 'docker_stats') {
      const r = await sshExec('docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.NetIO}}"');
      return { content: [{ type: 'text', text: r.stdout || r.stderr }] };
    }

    // ── docker_restart ─────────────────────────────────────────────────────
    if (name === 'docker_restart') {
      const service = args.service;
      if (!KNOWN_SERVICES.includes(service)) {
        return { content: [{ type: 'text', text: `❌ Serviço desconhecido: ${service}` }] };
      }
      const r = await dc(`docker compose restart ${service}`);
      return { content: [{ type: 'text', text: `✅ ${service} reiniciado.\n${r.stdout}\n${r.stderr}` }] };
    }

    // ── docker_compose_up ──────────────────────────────────────────────────
    if (name === 'docker_compose_up') {
      const buildFlag = args.build ? '--build' : '';
      const r = await dc(`docker compose up -d ${buildFlag}`, 120000);
      return { content: [{ type: 'text', text: r.stdout + '\n' + r.stderr }] };
    }

    // ── docker_compose_down ────────────────────────────────────────────────
    if (name === 'docker_compose_down') {
      const r = await dc('docker compose down');
      return { content: [{ type: 'text', text: r.stdout + '\n' + r.stderr }] };
    }

    // ── read_file ──────────────────────────────────────────────────────────
    if (name === 'read_file') {
      const r = await sshExec(`cat "${args.path}"`);
      if (r.code !== 0) return { content: [{ type: 'text', text: `❌ Erro ao ler arquivo: ${r.stderr}` }] };
      return { content: [{ type: 'text', text: r.stdout || '(arquivo vazio)' }] };
    }

    // ── write_file ─────────────────────────────────────────────────────────
    if (name === 'write_file') {
      // Escapa aspas simples no conteúdo para heredoc seguro
      const escaped = args.content.replace(/\\/g, '\\\\').replace(/'/g, "'\\''");
      const r = await sshExec(`cat > '${args.path}' << 'MCPEOF'\n${args.content}\nMCPEOF`, 15000);
      if (r.code !== 0 && r.stderr) {
        return { content: [{ type: 'text', text: `❌ Erro ao escrever: ${r.stderr}` }] };
      }
      return { content: [{ type: 'text', text: `✅ Arquivo escrito: ${args.path}` }] };
    }

    // ── shell ──────────────────────────────────────────────────────────────
    if (name === 'shell') {
      const r = await sshExec(args.command, 60000);
      const out = [r.stdout, r.stderr].filter(Boolean).join('\n');
      return { content: [{ type: 'text', text: out || `(exit ${r.code})` }] };
    }

    // ── health_check ───────────────────────────────────────────────────────
    if (name === 'health_check') {
      const [disk, mem, containers, stats] = await Promise.all([
        sshExec('df -h /'),
        sshExec('free -m'),
        dc('docker compose ps -a'),
        sshExec('docker stats --no-stream --format "{{.Name}}: CPU {{.CPUPerc}} | MEM {{.MemUsage}} ({{.MemPerc}})"'),
      ]);

      const report = [
        '=== 💾 DISCO ===',
        disk.stdout,
        '',
        '=== 🧠 MEMÓRIA RAM ===',
        mem.stdout,
        '',
        '=== 🐳 CONTAINERS ===',
        containers.stdout || containers.stderr,
        '',
        '=== 📊 STATS DOCKER ===',
        stats.stdout || stats.stderr,
      ].join('\n');

      return { content: [{ type: 'text', text: report }] };
    }

    return { content: [{ type: 'text', text: `❌ Ferramenta desconhecida: ${name}` }] };

  } catch (err) {
    return { content: [{ type: 'text', text: `❌ Erro SSH: ${err.message}` }] };
  }
});

// ─────────────────────────────────────────────────────────────────────────────
// START
// ─────────────────────────────────────────────────────────────────────────────
const transport = new StdioServerTransport();
await server.connect(transport);
console.error('MCP VPS SSH Server iniciado. Aguardando comandos via stdio...');
