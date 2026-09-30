#!/bin/bash
set -e

cd /var/www/html

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
  echo "[entrypoint] Generating APP_KEY..."
  php artisan key:generate --force || true
fi

# Run migrations + seed
if [ -n "$DATABASE_URL" ]; then
  echo "[entrypoint] Migrating with DATABASE_URL..."
  php artisan migrate --force || true
  php artisan db:seed --force || true
else
  echo "[entrypoint] Migrating with SQLite at /data/database.sqlite..."
  mkdir -p /data
  touch /data/database.sqlite
  php artisan migrate --force || true
  php artisan db:seed --force || true
fi

# Start Apache
echo "[entrypoint] Starting Apache on port ${PORT:-80}..."
exec apache2-foreground
