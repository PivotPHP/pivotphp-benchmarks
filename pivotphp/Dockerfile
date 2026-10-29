# PivotPHP voting API — Nginx + PHP-FPM (produtivo, idêntico entre frameworks).

# ---- Build: instala dependências ----
FROM composer:2 AS builder
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader \
    --ignore-platform-req=ext-pdo --ignore-platform-req=ext-pdo_pgsql --ignore-platform-req=ext-mbstring

# ---- Runtime: PHP-FPM + Nginx ----
FROM php:8.3.36-fpm

RUN apt-get update && apt-get install -y --no-install-recommends nginx libpq-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql opcache mbstring \
    && rm -rf /var/lib/apt/lists/*

# Tuning PHP (OPcache).
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-benchmark.ini

# FPM pool (pm.max_children idêntico entre frameworks).
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx (proxy reverso para o FPM na 127.0.0.1:9000).
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

COPY --from=builder /app/vendor ./vendor
COPY config ./config
COPY public ./public
COPY src ./src

EXPOSE 8080

# php-fpm em background + nginx em foreground (processo principal do container).
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
