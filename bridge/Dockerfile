FROM node:20

LABEL maintainer="TechInteligente"

# Dependências do Puppeteer/Chromium (estáveis para Debian/Node 20)
RUN apt-get update && apt-get install -y \
    chromium \
    fonts-ipafont-gothic \
    fonts-freefont-ttf \
    fonts-thai-tlwg \
    fonts-kacst \
    fonts-symbola \
    fonts-noto-color-emoji \
    libnss3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxrandr2 \
    libgbm1 \
    libpango-1.0-0 \
    libcairo2 \
    libasound2 \
    wget \
    ca-certificates \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Declare build arguments here to avoid busting cache for heavy layers above
ARG APP_ENV=production
ARG INTERNAL_KEY
ARG REDIS_URL
ARG WEBHOOK_URL

# Variáveis para o Puppeteer encontrar o Chromium corretamente
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

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
