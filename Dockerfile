# Use a Laravel-optimized image with everything pre-installed
FROM webdevops/php-apache:8.3

# Set DocumentRoot to Laravel's public/
ENV WEB_DOCUMENT_ROOT=/app/public
ENV WEB_DOCUMENT_INDEX=index.php

WORKDIR /app

# Copy app code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts --no-autoloader \
    && composer dump-autoload --no-dev --optimize

# Ensure storage directories exist and are writable
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             storage/app/public \
             bootstrap/cache \
    && chown -R application:application /app \
    && chmod -R 775 storage bootstrap/cache

# Persistent storage for SQLite (Render mounts /data)
RUN mkdir -p /data
VOLUME /data

# Render sets PORT env var (default Apache listens on 80)
ENV PORT=80
EXPOSE 80

# Startup: generate key, run migrations, start Apache (already started by base image)
# But we need to wrap it: run migrations BEFORE Apache starts
COPY <<'ENTRYPOINT_SCRIPT' /entrypoint.sh
#!/bin/bash
set -e

cd /app

# Generate APP_KEY if missing
php artisan key:generate --force 2>&1 | tail -1 || true

# Run migrations + seed
if [ -n "$DATABASE_URL" ]; then
  echo "Using DATABASE_URL for migrations"
  php artisan migrate --force 2>&1 | tail -5 || true
  php artisan db:seed --force 2>&1 | tail -3 || true
else
  echo "Using SQLite at /data/database.sqlite"
  mkdir -p /data
  touch /data/database.sqlite 2>/dev/null || true
  php artisan migrate --force 2>&1 | tail -5 || true
  php artisan db:seed --force 2>&1 | tail -3 || true
fi

# Start Apache (in foreground)
exec /usr/local/bin/apache2-foreground
ENTRYPOINT_SCRIPT

RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
