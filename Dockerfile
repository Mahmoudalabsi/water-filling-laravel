# Test: composer install ONLY (no dump-autoload) to isolate memory issue
FROM php:8.3-apache
RUN a2enmod rewrite headers
RUN apt-get update && apt-get install -y --no-install-recommends zip unzip git && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www/html/
WORKDIR /var/www/html
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1

# Just install, no autoload generation, no scripts
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs --no-autoloader

# Verify it actually worked
RUN ls vendor/ | head -20 && echo "=== vendor exists ==="
