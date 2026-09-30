# Minimal Laravel Dockerfile — diagnostic version
FROM php:8.3-apache

# Step 1: enable apache modules
RUN a2enmod rewrite headers

# Step 2: install system deps + PHP extensions in one layer
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

# Step 3: copy composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Step 4: copy app source
COPY . /var/www/html/
WORKDIR /var/www/html

# Step 5: point Apache docroot to Laravel's public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/apache2.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        >> /etc/apache2/apache2.conf

# Step 6: composer install (no scripts since APP_KEY not set yet)
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1
RUN composer install --no-dev --prefer-dist --no-scripts \
    && composer dump-autoload --no-dev --optimize

# Step 7: ensure storage dirs exist & are writable
RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache/data storage/logs bootstrap/cache /data \
    && chown -R www-data:www-data storage bootstrap/cache /data \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=file \
    CACHE_DRIVER=file \
    LOG_CHANNEL=stderr

EXPOSE 80

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
