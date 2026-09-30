# Simple, working Laravel Dockerfile for Render
FROM php:8.3-apache

# 1. Install deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libonig-dev libxml2-dev libcurl4-openssl-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql pdo_sqlite zip mbstring xml curl pcntl \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Enable mod_rewrite
RUN a2enmod rewrite

# 4. Configure DocumentRoot to /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# 5. AllowOverride All for /var/www/html/public (for .htaccess)
RUN printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

WORKDIR /var/www/html

# 6. Copy app code
COPY . .

# 7. Install PHP deps
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts --no-autoloader || true
RUN composer dump-autoload --no-dev --optimize || true

# 8. Storage permissions
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# 9. Persistent storage for SQLite
RUN mkdir -p /data
VOLUME /data

# 10. Render sets PORT env var. Apache listens on 80 by default
ENV PORT=80
EXPOSE 80

# 11. Startup script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
