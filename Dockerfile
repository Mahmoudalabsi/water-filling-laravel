# syntax=docker/dockerfile:1.6
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

# Configure Apache DocumentRoot to /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>' >> /etc/apache2/apache2.conf

# Install PHP deps
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts \
    && composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Persistent storage
RUN mkdir -p /data
VOLUME /data

ENV PORT=80

# Render sends PORT env var. Apache listens on 80 by default.
EXPOSE 80

# Startup: generate key, migrate, seed, then start Apache
CMD sh -c '\
  php artisan key:generate --force || true && \
  if [ -n "$DATABASE_URL" ]; then \
    php artisan migrate --force || true && \
    php artisan db:seed --force || true; \
  else \
    touch /data/database.sqlite 2>/dev/null || true && \
    php artisan migrate --force || true && \
    php artisan db:seed --force || true; \
  fi && \
  apache2-foreground'
