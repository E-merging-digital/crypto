# syntax=docker/dockerfile:1.6
FROM php:8.3-apache-bookworm

ARG DEBIAN_FRONTEND=noninteractive
ARG SQLITE_VER=3460000
ENV LD_LIBRARY_PATH=/usr/local/lib
ENV CPPFLAGS="-I/usr/local/include" LDFLAGS="-L/usr/local/lib"

# Dépendances de build et bibliothèques
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      ca-certificates wget curl git build-essential pkg-config xz-utils autoconf \
      libicu-dev libzip-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
      libgmp-dev; \
    rm -rf /var/lib/apt/lists/*

# SQLite 3.46.0 (COLUMN_METADATA + JSON1)
RUN set -eux; \
    wget -O /tmp/sqlite.tar.gz "https://www.sqlite.org/2024/sqlite-autoconf-${SQLITE_VER}.tar.gz"; \
    mkdir -p /usr/src/sqlite; \
    tar -xzf /tmp/sqlite.tar.gz -C /usr/src/sqlite --strip-components=1; \
    cd /usr/src/sqlite; \
    ./configure --prefix=/usr/local \
      CFLAGS="-O2 -fPIC -DSQLITE_ENABLE_COLUMN_METADATA=1 -DSQLITE_ENABLE_JSON1=1"; \
    make -j"$(nproc)"; \
    make install; \
    echo "/usr/local/lib" > /etc/ld.so.conf.d/zz-local-sqlite3.conf; \
    ldconfig; \
    sqlite3 --version

# Extensions PHP (gd/zip/intl/opcache/gmp), puis sqlite3/pdo_sqlite avec sources garanties
RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" gd zip intl opcache gmp; \
    docker-php-source extract || true; \
    if [ ! -f /usr/src/php/ext/sqlite3/config.m4 ] || [ ! -f /usr/src/php/ext/pdo_sqlite/config.m4 ]; then \
      PHP_V="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION, ".", PHP_RELEASE_VERSION;')"; \
      curl -fsSL "https://www.php.net/distributions/php-${PHP_V}.tar.xz" -o /tmp/php-src.tar.xz; \
      mkdir -p /usr/src/php-src; \
      tar -xJf /tmp/php-src.tar.xz -C /usr/src/php-src --strip-components=1; \
      rm -rf /usr/src/php/ext/sqlite3 /usr/src/php/ext/pdo_sqlite || true; \
      cp -r /usr/src/php-src/ext/sqlite3 /usr/src/php/ext/sqlite3; \
      cp -r /usr/src/php-src/ext/pdo_sqlite /usr/src/php/ext/pdo_sqlite; \
    fi; \
    [ -f /usr/src/php/ext/sqlite3/config.m4 ] || cp /usr/src/php/ext/sqlite3/config0.m4 /usr/src/php/ext/sqlite3/config.m4; \
    [ -f /usr/src/php/ext/pdo_sqlite/config.m4 ] || cp /usr/src/php/ext/pdo_sqlite/config0.m4 /usr/src/php/ext/pdo_sqlite/config.m4; \
    docker-php-ext-configure sqlite3 --with-sqlite3=/usr/local; \
    docker-php-ext-configure pdo_sqlite --with-pdo-sqlite=/usr/local; \
    docker-php-ext-install -j"$(nproc)" sqlite3 pdo_sqlite; \
    docker-php-source delete || true; \
    php -m | grep -iE 'pdo_sqlite|sqlite3'; \
    php -r 'echo (class_exists("SQLite3")? (SQLite3::version()["lib"]??"n/a") : "no-sqlite3"), PHP_EOL;'

# Apache
RUN a2enmod rewrite headers

WORKDIR /var/www/html

HEALTHCHECK --interval=30s --timeout=3s CMD php -v >/dev/null || exit 1
