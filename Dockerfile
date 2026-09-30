FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git curl \
    && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

# Copy composer.json and try dry-run
COPY composer.json /app/composer.json
WORKDIR /app
RUN composer install --dry-run --no-dev --prefer-dist --no-scripts --ignore-platform-reqs 2>&1 | tail -50
