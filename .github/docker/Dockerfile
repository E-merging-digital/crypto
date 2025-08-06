FROM php:8.3-apache

# 1 couche RUN pour installer git + libs et activer extensions
RUN apt-get update && \
    apt-get install -y \
      git \                                # pour checkout + config git :contentReference[oaicite:4]{index=4}
      libgmp-dev libpng-dev libjpeg-dev libwebp-dev && \
    docker-php-ext-install \
      gmp \                               # activation de ext-gmp :contentReference[oaicite:5]{index=5}
      gd &&                               # activation de ext-gd :contentReference[oaicite:6]{index=6}
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

# Copier Composer depuis l’image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
