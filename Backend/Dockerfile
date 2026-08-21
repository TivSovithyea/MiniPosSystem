# Pin Debian Bookworm so upstream changes to the floating PHP image do not
# unexpectedly change the packages available during a Render deployment.
FROM php:8.4-cli-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        zip \
        xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN composer config --global source-fallback true

FROM base AS test

COPY . .

RUN composer install \
    --prefer-dist \
    --no-interaction \
    --no-progress

CMD ["php", "artisan", "test"]

FROM base AS production

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

RUN chmod -R 775 storage bootstrap/cache

CMD ["sh", "-c", "php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
