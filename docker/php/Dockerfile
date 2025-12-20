FROM php:8.3-fpm-alpine

# Argumentos para usuário
ARG user=www
ARG uid=1000

# Instalar dependências do sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    mysql-client \
    supervisor \
    nodejs \
    npm

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Instalar Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário para rodar Composer e Artisan
RUN adduser -D -u $uid $user

# Criar diretórios necessários e ajustar permissões
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R $user:$user /var/www

# Configurar diretório de trabalho
WORKDIR /var/www

# Trocar para usuário não-root
USER $user

# Expor porta PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
