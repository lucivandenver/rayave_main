#!/usr/bin/env bash
set -e

PORT="${PORT:-80}"

# Configure Apache to listen on dynamic $PORT (Railway sets $PORT; local Docker defaults to 80)
printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
printf '%s\n' \
    'ServerName localhost' \
    "<VirtualHost *:${PORT}>" \
    '    ServerAdmin webmaster@localhost' \
    '    DocumentRoot /var/www/html' \
    '    Alias /flowershop-main /var/www/html' \
    '    <Directory /var/www/html>' \
    '        Options Indexes FollowSymLinks' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

# Ensure runtime upload and log directories exist and are writable
mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/support_attachments \
    /var/www/html/storage/custom_designs \
    /var/www/html/assets/images/products

chown -R www-data:www-data /var/www/html/storage /var/www/html/assets/images 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/assets/images 2>/dev/null || true

# Auto-import SQL dump once if connected to an empty database (e.g. fresh Railway MySQL)
if [ -f /var/www/html/database/auto_import.php ]; then
    php /var/www/html/database/auto_import.php || true
fi

exec apache2-foreground
