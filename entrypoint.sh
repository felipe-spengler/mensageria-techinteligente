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

# Run migrations if database is ready
php artisan migrate --force

# Publish Filament assets to fix 404s
php artisan filament:assets

# Start Apache in foreground
apache2-foreground
