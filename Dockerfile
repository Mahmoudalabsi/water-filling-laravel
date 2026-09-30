# Diagnostic: composer install with verbose error capture
FROM php:8.3-apache
RUN a2enmod rewrite headers
RUN apt-get update && apt-get install -y --no-install-recommends zip unzip git && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www/html/
WORKDIR /var/www/html
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1

# Run composer install with verbose output, capture to a debug file
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs --no-autoloader -vvv 2>&1 | tee /tmp/composer.log; \
    if [ ! -d /var/www/html/vendor ]; then \
        echo "=== COMPOSER FAILED ==="; \
        cat /tmp/composer.log | tail -50; \
        exit 1; \
    fi
