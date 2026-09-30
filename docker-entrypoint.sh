#!/bin/bash
set -e

cd /var/www/html

echo "[entrypoint] Laravel deploy starting..."

# Ensure storage dirs exist (in case the image's mkdir got wiped)
mkdir -p storage/framework/sessions storage/framework/views \
         storage/framework/cache/data storage/logs bootstrap/cache /data
chown -R www-data:www-data storage bootstrap/cache /data

# Initialize SQLite database file if missing
if [ ! -f /data/database.sqlite ]; then
  echo "[entrypoint] Creating SQLite database at /data/database.sqlite"
  touch /data/database.sqlite
  chown www-data:www-data /data/database.sqlite
fi

# Generate APP_KEY if not set as an env var
if [ -z "$APP_KEY" ]; then
  echo "[entrypoint] Generating APP_KEY..."
  php artisan key:generate --force || echo "[entrypoint] WARN: key:generate failed"
fi

# Cache config + routes for performance (ignore failures gracefully)
echo "[entrypoint] Caching config & routes..."
php artisan config:cache 2>/dev/null || echo "[entrypoint] WARN: config:cache failed"
php artisan route:cache 2>/dev/null || echo "[entrypoint] WARN: route:cache failed"
php artisan view:cache 2>/dev/null || echo "[entrypoint] WARN: view:cache failed"

# Run migrations
echo "[entrypoint] Running migrations..."
php artisan migrate --force || echo "[entrypoint] WARN: migrate failed"

# Seed (idempotent — seeder uses firstOrCreate internally)
echo "[entrypoint] Seeding database..."
php artisan db:seed --force || echo "[entrypoint] WARN: seed failed"

# Start Apache
echo "[entrypoint] Starting Apache on port ${PORT:-80}..."
exec apache2-foreground
