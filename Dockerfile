# Dockerfile otimizado para rebuild incremental e cache eficiente
FROM php:8.4-apache

# Metadata
LABEL maintainer="TechInteligente" \
      description="SaaS WhatsApp App"

# Instala dependências necessárias (rodar só se mudar a imagem base)
RUN apt-get update && apt-get install -y \
    curl \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    gnupg && \
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
  apt-get install -y nodejs && \
  apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions e Apache features
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip && \
    a2enmod rewrite

# Apache DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Composer (cache layer) e dependências PHP
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader --no-scripts --ignore-platform-reqs

# Node deps (cache atômico)
COPY package.json ./
# Caso não exista package-lock.json, não falha; `npm install` seguirá.
RUN npm config set fund false && npm config set audit false && \
    if [ -f package-lock.json ]; then \
      npm ci --silent --legacy-peer-deps --progress=false --no-audit; \
    else \
      npm install --silent --legacy-peer-deps --progress=false --no-audit; \
    fi

# Copia código
COPY . ./

# Build assets (apenas rodar se recursos mudarem)
RUN npm run build --silent

# Crias pastas necessárias em runtime e definem permissões
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache && \
    touch database/database.sqlite && \
    composer dump-autoload --optimize --no-scripts && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Ambiente de produção (pode ser sobrescrito via .env)
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 80

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
