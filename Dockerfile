# ---- Laravel 11 on Render — minimal, no zip ext needed ----
FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install system deps (zip/unzip/git for composer, NO libzip-dev to skip docker-php-ext-install zip)
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Copy composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app source (respects .dockerignore)
COPY . /var/www/html/
WORKDIR /var/www/html

# Composer install — split into 2 RUN commands to reduce peak memory.
# Use --prefer-source to avoid zip ext requirement, --no-autoloader on install
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1
RUN composer install --no-dev --prefer-source --no-scripts --ignore-platform-reqs --no-autoloader
RUN composer dump-autoload --no-dev --classmap-authoritative

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
