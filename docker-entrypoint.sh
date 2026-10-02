#!/bin/bash
set -e

cd /var/www/html

echo "[entrypoint] Laravel deploy starting..."

# 1) Ensure runtime directories exist
mkdir -p storage/framework/sessions storage/framework/views \
         storage/framework/cache/data storage/logs bootstrap/cache /data

# 2) Create .env if missing (key:generate needs a file to write to)
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[entrypoint] Creating .env from .env.example"
  cp .env.example .env
fi

# 3) Generate APP_KEY if not provided via environment
if [ -z "$APP_KEY" ]; then
  echo "[entrypoint] Generating APP_KEY..."
  php artisan key:generate --force || echo "[entrypoint] WARN: key:generate failed"
fi

# 4) Prepare SQLite database file
if [ ! -f /data/database.sqlite ]; then
  echo "[entrypoint] Creating SQLite database at /data/database.sqlite"
  touch /data/database.sqlite
fi

# 5) Cache config/routes/views for performance (non-fatal on failure)
echo "[entrypoint] Caching config & routes..."
php artisan config:cache 2>/dev/null || echo "[entrypoint] WARN: config:cache failed"
php artisan route:cache 2>/dev/null || echo "[entrypoint] WARN: route:cache failed"
php artisan view:cache 2>/dev/null || echo "[entrypoint] WARN: view:cache failed"

# 6) Run migrations + seed (idempotent)
echo "[entrypoint] Running migrations..."
php artisan migrate --force || echo "[entrypoint] WARN: migrate failed"
echo "[entrypoint] Seeding database..."
php artisan db:seed --force || echo "[entrypoint] WARN: seed failed"

# 7) Fix ownership for www-data (artisan ran as root, created files as root)
chown -R www-data:www-data /data storage bootstrap/cache

echo "[entrypoint] Starting Apache on port ${PORT:-80}..."
exec apache2-foreground
