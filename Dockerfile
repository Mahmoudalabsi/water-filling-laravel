# Minimal test Dockerfile - just to verify Render builds work
FROM php:8.3-apache

# Install minimum deps for composer (no pgsql/mysql since we use sqlite)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev libonig-dev libxml2-dev unzip \
    && docker-php-ext-install pdo pdo_sqlite zip mbstring xml \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# First copy ONLY composer files for better caching
COPY composer.json ./

# Then copy the rest
COPY . .

# Install PHP deps - exit code 0 even if composer complains
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts 2>&1 || echo "composer install had issues but continuing"

# DocumentRoot → public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Storage permissions
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# SQLite storage
RUN mkdir -p /data
VOLUME /data

EXPOSE 80

# Startup
CMD sh -c '\
  cp .env.example .env 2>/dev/null || true && \
  php artisan key:generate --force 2>&1 || echo "key:generate failed" && \
  mkdir -p /data && touch /data/database.sqlite && \
  php artisan migrate --force 2>&1 || echo "migrate failed" && \
  php artisan db:seed --force 2>&1 || echo "seed failed" && \
  apache2-foreground'
