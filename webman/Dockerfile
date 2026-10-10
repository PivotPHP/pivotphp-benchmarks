# Webman (async, worker persistente) — não usa Nginx/PHP-FPM.

FROM composer:2 AS builder
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader \
    --ignore-platform-req=ext-pdo --ignore-platform-req=ext-pdo_pgsql

FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql opcache pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/vendor ./vendor
COPY . .

RUN mkdir -p runtime/logs && chmod -R 777 runtime

EXPOSE 8080

CMD ["php", "start.php", "start"]
