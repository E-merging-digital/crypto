FROM php:8.3-apache

# Installer git, bash + libs, et activer ext-gmp/gd
RUN apt-get update && \
    apt-get install -y \
      git \
      bash \            # ← Ajout de bash ici
      libgmp-dev libpng-dev libjpeg-dev libwebp-dev && \
    docker-php-ext-install \
      gmp \
      gd && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
