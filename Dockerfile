FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends zip unzip git curl \
    && rm -rf /var/lib/apt/lists/*

# Test 1: Can we reach packagist metadata?
RUN curl -sSL -o /tmp/packagist.json -w "packagist: %{http_code} | size: %{size_download} | time: %{time_total}s\n" \
      https://repo.packagist.org/p2/psr/log.json \
    && head -c 200 /tmp/packagist.json \
    && echo "" \
    && echo "=== packagist OK ==="

# Test 2: Can we download a GitHub release ZIP directly?
RUN curl -sSL -o /tmp/test.zip -w "github-zip: %{http_code} | size: %{size_download} | time: %{time_total}s\n" \
      https://github.com/php-fig/log/archive/refs/tags/3.0.2.zip \
    && ls -la /tmp/test.zip

# Test 3: Install composer and try real install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /app
RUN composer init --name=test/test --no-interaction
RUN composer require psr/log:^3.0 --no-scripts --ignore-platform-reqs --no-autoloader 2>&1; \
    echo "=== composer require exit: $? ==="; \
    ls -la vendor/ 2>&1; \
    cat /root/.composer/cache/psr/log/* 2>&1 | head -50
