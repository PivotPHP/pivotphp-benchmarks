FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    && docker-php-ext-install zip sockets \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Set PHP configuration for ReactPHP
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/docker-php-opcache.ini

# Expose port for ReactPHP server
EXPOSE 8000

# Keep container running for benchmarks
CMD ["tail", "-f", "/dev/null"]