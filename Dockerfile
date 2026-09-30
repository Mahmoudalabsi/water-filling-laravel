# ---- Laravel 11 on Render — multi-stage build (composer in dedicated image) ----

# Stage 1: install composer deps in the official composer image (lower memory footprint)
FROM composer:2 AS builder
WORKDIR /app
COPY composer.json composer.lock* ./
# Run install — no scripts (no APP_KEY), no dev packages, ignore platform reqs
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs \
    && composer dump-autoload --no-dev --optimize

# Stage 2: runtime
FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install only system deps we strictly need for runtime (libzip for the zip ext)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip4 \
    && rm -rf /var/lib/apt/lists/*

# Build PHP extensions: pdo_sqlite (no deps) + zip (needs libzip-dev headers + then we can remove)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && apt-get purge -y --auto-remove libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Copy app source (respects .dockerignore — vendor/ excluded)
COPY . /var/www/html/
WORKDIR /var/www/html

# Copy pre-built vendor/ from builder stage
COPY --from=builder /app/vendor /var/www/html/vendor

# Point Apache docroot to Laravel's /public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/apache2.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n    Options -Indexes +FollowSymLinks\n</Directory>\n' \
        >> /etc/apache2/apache2.conf

# Ensure storage dirs exist & are writable
RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache/data storage/logs bootstrap/cache /data \
    && chown -R www-data:www-data storage bootstrap/cache /data \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY= \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=file \
    CACHE_DRIVER=file \
    LOG_CHANNEL=stderr

EXPOSE 80

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
