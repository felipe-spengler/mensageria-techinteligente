FROM node:20

LABEL maintainer="TechInteligente"

# Dependências do Puppeteer (só muda se base muda)
RUN apt-get update && apt-get install -y \
    chromium \
    ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release wget xdg-utils libnss3 libdrm2 libxkbcommon0 \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY package.json ./
COPY package-lock.json ./

RUN npm config set fund false && npm config set audit false && \
    npm install --silent --legacy-peer-deps --progress=false --no-audit || \
    npm install --silent --legacy-peer-deps --progress=false

COPY . ./

EXPOSE 3000

# Limit Node.js heap to 3 GB to prevent OOM
CMD ["node", "--max-old-space-size=3072", "index.js"]

# Docker health check – asks /health every 30s; 3 failures = unhealthy
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD wget -qO- http://localhost:3000/health || exit 1
