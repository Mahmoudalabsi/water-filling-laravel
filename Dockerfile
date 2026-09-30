# syntax=docker/dockerfile:1.6
# Multi-stage build: PHP 8.3 with Apache, optimized for Render

FROM php:8.3-apache

# Enable mod_rewrite for Laravel
RUN a2enmod rewrite

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
    unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql pdo_sqlite zip mbstring xml curl pcntl \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app code
COPY . .

# Configure Apache: DocumentRoot → public/, allow overrides
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

# Install PHP deps
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts --no-autoloader \
    && composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# PHP production config
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/custom.ini

# Persistent storage for SQLite (Render mounts /data via disk)
RUN mkdir -p /data
VOLUME /data

# Render sets PORT env var
ENV PORT=80
EXPOSE 80

# Startup: generate key, run migrations, start Apache
CMD sh -c '\
  php artisan key:generate --force 2>&1 | tail -1 || true; \
  if [ -n "$DATABASE_URL" ]; then \
    php artisan migrate --force 2>&1 | tail -5 || true; \
    php artisan db:seed --force 2>&1 | tail -3 || true; \
  else \
    mkdir -p /data && touch /data/database.sqlite 2>/dev/null || true; \
    php artisan migrate --force 2>&1 | tail -5 || true; \
    php artisan db:seed --force 2>&1 | tail -3 || true; \
  fi; \
  apache2-foreground'
