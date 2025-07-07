FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip \
    intl \
    opcache \
    pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure OPcache for benchmarking
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP for benchmarking
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/benchmark.ini \
    && echo "max_execution_time=0" >> /usr/local/etc/php/conf.d/benchmark.ini \
    && echo "error_reporting=E_ERROR" >> /usr/local/etc/php/conf.d/benchmark.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/benchmark.ini

# Install benchmarking tools
RUN composer global require phpbench/phpbench

# Add composer global bin to PATH
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /benchmarks

CMD ["php", "-v"]