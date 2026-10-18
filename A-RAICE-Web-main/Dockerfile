FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        bash \
        curl \
        gosu \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pdo_pgsql zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && printf "upload_max_filesize=10M\npost_max_size=12M\nmax_execution_time=180\n" \
        > /usr/local/etc/php/conf.d/production.ini \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
    && npm install --no-audit --no-fund \
    && npm run build \
    && php artisan storage:link \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 8080

ENTRYPOINT ["/bin/sh", "/var/www/html/docker/start.sh"]
