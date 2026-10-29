# Microbench de roteamento — PHP CLI isolado, com uma pasta vendor/ por versão do core-routing.

# ---- Build: instala cada versão a partir do seu composer.lock ----
FROM composer:2.8.12 AS builder
WORKDIR /bench
COPY versions/ versions/
RUN for dir in versions/*/; do \
        composer install --working-dir="$dir" --no-dev --no-scripts --prefer-dist \
            --optimize-autoloader --no-interaction --no-progress || exit 1; \
    done

# ---- Runtime ----
FROM php:8.3.36-cli

# OPcache ligado no CLI como no PHP-FPM do benchmark HTTP; JIT desligado para medir o interpretador.
RUN docker-php-ext-install opcache \
    && { echo "opcache.enable_cli=1"; echo "opcache.jit=off"; echo "opcache.jit_buffer_size=0"; } \
        > /usr/local/etc/php/conf.d/zz-microbench.ini

RUN useradd --create-home bench
USER bench
WORKDIR /bench
COPY --from=builder --chown=bench:bench /bench/versions/ versions/
COPY --chown=bench:bench bench.php ./

ENTRYPOINT ["php", "bench.php"]
