# Diagnostic: just apt-get install, no docker-php-ext-install
FROM php:8.3-apache
RUN a2enmod rewrite headers
RUN apt-get update && apt-get install -y --no-install-recommends zip unzip git && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_sqlite
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www/html/
WORKDIR /var/www/html
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs --no-autoloader
RUN composer dump-autoload --no-dev --classmap-authoritative
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache /data \
    && chown -R www-data:www-data storage bootstrap/cache /data \
    && chmod -R 775 storage bootstrap/cache
ENV APP_ENV=production APP_DEBUG=false APP_KEY= DB_CONNECTION=sqlite DB_DATABASE=/data/database.sqlite SESSION_DRIVER=file CACHE_DRIVER=file LOG_CHANNEL=stderr
EXPOSE 80
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["docker-entrypoint.sh"]
