# syntax=docker/dockerfile:1.6
FROM php:8.3-cli

# System deps + PHP extensions (PDO for SQLite, PostgreSQL, MySQL)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
    unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql pdo_sqlite zip mbstring xml curl pcntl \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app code
COPY . .

# Install PHP deps (no dev in prod)
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts \
    && composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Persistent storage for SQLite (Render mounts /data via disk)
RUN mkdir -p /data
VOLUME /data

ENV PORT=8000

# Start script:
#   1. APP_KEY if missing
#   2. If SQLite + no DB file → create file, migrate, seed
#   3. If DATABASE_URL (Postgres/MySQL) → migrate + seed
#   4. Start PHP server
CMD sh -c '\
  php artisan key:generate --force || true && \
  if [ -n "$DATABASE_URL" ]; then \
    php artisan migrate --force || true && \
    php artisan db:seed --force || true; \
  else \
    touch /data/database.sqlite 2>/dev/null || touch database.sqlite && \
    php artisan migrate --force || true && \
    php artisan db:seed --force || true; \
  fi && \
  php artisan storage:link || true && \
  php artisan serve --host=0.0.0.0 --port=${PORT:-8000}'
