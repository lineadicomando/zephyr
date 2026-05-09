# syntax=docker/dockerfile:1

# Stage 1: build frontend assets
FROM docker.io/library/node:22-alpine AS assets
WORKDIR /build
COPY package*.json ./
RUN npm ci --ignore-scripts
COPY . .
RUN npm run build

# Stage 2: PHP-FPM runtime
FROM docker.io/library/php:8.5-fpm-alpine

RUN <<EOF
apk add --no-cache \
    bash \
    rsync \
    netcat-openbsd \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev
docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache
pecl install redis
docker-php-ext-enable redis
rm -rf /tmp/pear
EOF

COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN <<EOF
composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction
EOF

COPY . .
COPY --from=assets /build/public/build ./public/build

RUN <<EOF
composer dump-autoload --optimize --no-dev --no-interaction
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
EOF

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zephyr.ini
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data
EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
