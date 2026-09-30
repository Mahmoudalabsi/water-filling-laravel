FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git curl \
    && rm -rf /var/lib/apt/lists/*
# Install composer via installer script
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Verify composer works
RUN composer --version
