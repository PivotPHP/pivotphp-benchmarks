FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install opcache

# Configure OPcache for maximum performance
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.jit=1255'; \
    echo 'opcache.jit_buffer_size=128M'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP
RUN { \
    echo 'memory_limit=512M'; \
    echo 'max_execution_time=0'; \
} > /usr/local/etc/php/conf.d/benchmark.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Create .env file
RUN cp .env.example .env

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Run benchmarks by default
CMD ["php", "benchmarks/run.php"]