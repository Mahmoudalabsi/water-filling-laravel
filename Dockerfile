# ---- Laravel 11 on Render (Apache + PHP 8.3 + SQLite) ----
FROM php:8.3-apache

# 1) System packages + PHP extensions (pdo_sqlite for SQLite, zip for composer)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev zip unzip git libonig-dev \
    && docker-php-ext-install pdo pdo_sqlite zip mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# 2) Composer (copy from official image — no install needed)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3) App source
WORKDIR /var/www/html
COPY . .

# 4) Point Apache DocumentRoot to Laravel's /public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/apache2.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n    Options -Indexes +FollowSymLinks\n</Directory>\n' \
        >> /etc/apache2/apache2.conf

# 5) Install PHP dependencies (no scripts — they need APP_KEY)
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts \
    && composer dump-autoload --no-dev --optimize

# 6) Writable directories (Laravel requires these to exist & be writable)
RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache/data storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 7) Persistent /data directory for SQLite (Render persistent disk)
RUN mkdir -p /data && chown www-data:www-data /data

# 8) Default environment (Render env vars override these at runtime)
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY= \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=file \
    CACHE_DRIVER=file \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

EXPOSE 80

# 9) Entrypoint: key:generate → migrate → seed (idempotent) → apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
