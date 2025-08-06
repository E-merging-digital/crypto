FROM php:8.3-apache

# Installer dépendances système nécessaires
RUN apt-get update \
    && apt-get install -y libgmp-dev libpng-dev libjpeg-dev libwebp-dev \
    && docker-php-ext-install \
         gmp \
         gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copier Composer depuis l’image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

