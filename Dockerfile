FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git curl \
    && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy ONLY composer.json (this approach worked)
COPY composer.json /app/composer.json
WORKDIR /app
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1 COMPOSER_HOME=/tmp

# Real install (no dry-run, with --no-autoloader)
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs --no-autoloader

# Verify vendor exists
RUN ls -la vendor/ | head -10 && echo "=== VENDOR OK ==="
