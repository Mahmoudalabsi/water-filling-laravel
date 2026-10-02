# ---- Laravel 11 on Render — MINIMAL build (vendor/ pre-built by GitHub Actions) ----
# NOTE: php:8.3-apache ALREADY includes pdo_sqlite, sqlite3, mbstring, etc.
#       So no docker-php-ext-install and no composer install are needed here.

FROM php:8.3-apache

# Enable Apache modules (rewrite for Laravel routes)
RUN a2enmod rewrite headers

# Copy app source (vendor/ is now committed to the repo by GitHub Actions)
COPY . /var/www/html/
WORKDIR /var/www/html

# Point Apache DocumentRoot to Laravel's /public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/apache2.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n    Options -Indexes +FollowSymLinks\n</Directory>\n' \
        >> /etc/apache2/apache2.conf

# Ensure storage dirs exist & are writable by www-data
RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache/data storage/logs bootstrap/cache /data \
    && chown -R www-data:www-data storage bootstrap/cache /data \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=cookie \
    CACHE_DRIVER=file \
    LOG_CHANNEL=stderr

EXPOSE 80

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
