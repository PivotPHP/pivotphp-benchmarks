# Dockerfile para Benchmark Científico PivotPHP Core
# Ambiente determinístico e reproduzível

FROM php:8.2-cli

# Instalar extensões necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    procps \
    htop \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install opcache

# Configuração OPcache/JIT padronizada
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

# Configuração de benchmark
RUN { \
    echo 'memory_limit=512M'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_time=60'; \
    echo 'post_max_size=100M'; \
    echo 'upload_max_filesize=100M'; \
    echo 'expose_php=Off'; \
} > /usr/local/etc/php/conf.d/benchmark.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /app

# Copiar dependências primeiro (cache layer)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copiar código da aplicação
COPY . .

# Gerar autoloader otimizado
RUN composer dump-autoload --optimize --no-dev

# Configurar permissões
RUN chown -R www-data:www-data /app

# Expor porta
EXPOSE 8080

# Comando padrão: servidor HTTP para benchmarks
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]