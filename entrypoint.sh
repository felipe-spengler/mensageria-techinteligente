#!/bin/bash

# Ensure storage structure exists in the volume
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Fix permissions for Apache user
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
if [ -f /var/www/html/database/database.sqlite ]; then
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi

# Wait for database if using MySQL
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for MySQL to start..."
    while ! timeout 1 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null; do
      sleep 2
    done
    echo "MySQL is up!"
fi

# Run migrations if database is ready
php artisan migrate --force --seed

# Publish Filament assets to fix 404s
php artisan filament:assets

# If arguments are passed, execute them (for worker/etc), else start Apache
if [ "$#" -gt 0 ]; then
    echo "Executing command: $@"
    exec "$@"
else
    echo "Starting Apache..."
    exec apache2-foreground
fi
