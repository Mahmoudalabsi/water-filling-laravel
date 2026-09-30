# syntax=docker/dockerfile:1.6
FROM php:8.3-cli

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
    unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql zip mbstring xml curl pcntl \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app code
COPY . .

# Install PHP deps (no dev in prod)
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts || true

# Permissions for storage & bootstrap/cache
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Render runs as non-root by default; let's bind to PORT env var
ENV PORT=8000

# Start script: generate key, run migrations, start PHP server
CMD sh -c '\
  php artisan key:generate --force || true && \
  php artisan migrate --force || true && \
  php artisan storage:link || true && \
  php artisan serve --host=0.0.0.0 --port=${PORT:-8000}'
