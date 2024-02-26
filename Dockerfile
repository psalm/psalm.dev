FROM php:8.3-apache

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN apt-get update && apt-get install -y \
  unzip \
  git \
  && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite && a2enmod headers
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install opcache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo -e "\nzend.assertions=1\nopcache.enable_cli=true\nopcache.jit_buffer_size=512M\nopcache.jit=1205" >> "$PHP_INI_DIR/php.ini"
