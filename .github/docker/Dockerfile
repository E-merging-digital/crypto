FROM php:8.3-apache

# Installer dépendances système nécessaires et GMP
RUN apt-get update \
    && apt-get install -y libgmp-dev git unzip zip libonig-dev libzip-dev libxml2-dev \
    && docker-php-ext-install gmp pdo pdo_mysql filter # adapte selon ce dont tu as besoin pour Drupal/Behat \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer (récupérer depuis l'image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
