
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader


FROM php:8.4-apache AS runner

# Instala dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        zip \
        opcache \
        gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilita módulos essenciais do Apache
RUN a2enmod rewrite headers env setenvif

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    DirectoryIndex index.php\n\
    FallbackResource /index.php\n\
    CGIPassAuth On\n\
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1\n\
</Directory>' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Configuração de produção do PHP
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Variáveis padrão de ambiente para compilação no container
ENV APP_ENV=prod \
    APP_DEBUG=0 \
    PORT=80 \
    DATABASE_URL="mysql://build:build@127.0.0.1:3306/build"

# Copia dependências do Composer
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copia todo o código-fonte da aplicação
COPY . /var/www/html

# Copia binário do Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Otimiza
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev && \
    php bin/console assets:install public --no-interaction && \
    mkdir -p var/cache var/log public/uploads && \
    chown -R www-data:www-data var public/uploads && \
    chmod -R 775 var public/uploads

# Script de inicialização
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN tr -d '\r' < /usr/local/bin/docker-entrypoint > /usr/local/bin/docker-entrypoint.tmp && \
    mv /usr/local/bin/docker-entrypoint.tmp /usr/local/bin/docker-entrypoint && \
    chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
