FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git curl \
    && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

# Copy ONLY composer.json (clean dir, no app code)
COPY composer.json /app/composer.json
WORKDIR /app

# Real composer install, capture verbose output to file
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1 COMPOSER_HOME=/tmp
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs --no-autoloader -vvv 2>&1 | tail -80
