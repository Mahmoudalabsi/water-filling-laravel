FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git curl \
    && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /app

# Initialize minimal project and require just ONE tiny package
RUN composer init --name=test/test --no-interaction \
    && composer require psr/log:^3.0 --no-scripts --ignore-platform-reqs --no-autoloader \
    && ls vendor/psr/log/

# Save exit code
RUN echo "=== BUILD SUCCEEDED ==="
