FROM php:8.3-apache
RUN a2enmod rewrite headers

# Copy composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app source
COPY . /var/www/html/
WORKDIR /var/www/html

# Install PHP deps (no scripts, no dev). Use --prefer-source to avoid zip extension requirement.
ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_NO_INTERACTION=1
RUN composer install --no-dev --prefer-dist --no-scripts --ignore-platform-reqs \
    && composer dump-autoload --no-dev --optimize
