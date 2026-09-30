FROM php:8.3-apache
RUN echo "build system test" > /tmp/test.txt && a2enmod rewrite
COPY . /var/www/html/
