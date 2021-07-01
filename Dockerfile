FROM php:7.3-apache

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite && a2enmod headers
RUN docker-php-ext-install pdo_mysql
RUN apt-get update && apt-get install -y \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*
