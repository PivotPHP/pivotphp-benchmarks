FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    docker.io \
    docker-compose \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pcntl

# Configure PHP
RUN { \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=0'; \
} > /usr/local/etc/php/conf.d/orchestrator.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /orchestrator

# Copy orchestrator scripts
COPY scripts/ /scripts/
COPY shared/ /shared/

# Make scripts executable
RUN chmod +x /scripts/*.sh 2>/dev/null || true

CMD ["tail", "-f", "/dev/null"]