FROM node:22-bookworm-slim AS assets

WORKDIR /app
COPY . .
RUN npm ci && npm run build

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gosu \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd mbstring pcntl pdo_mysql xml zip \
    && a2enmod headers rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && for module in mpm_event mpm_worker; do \
        if a2query -m "$module" >/dev/null 2>&1; then a2dismod "$module"; fi; \
    done \
    && a2enmod mpm_prefork \
    && printf '%s\n' \
        'ServerTokens Prod' \
        'ServerSignature Off' \
        'Header always set X-Content-Type-Options "nosniff"' \
        'Header always set X-Frame-Options "SAMEORIGIN"' \
        'Header always set Referrer-Policy "strict-origin-when-cross-origin"' \
        'Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"' \
        > /etc/apache2/conf-available/security-headers.conf \
    && a2enconf security-headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY scripts/container-entrypoint.sh /usr/local/bin/container-entrypoint
RUN chmod 755 /usr/local/bin/container-entrypoint

EXPOSE 8080
ENTRYPOINT ["sh", "/usr/local/bin/container-entrypoint"]
CMD ["apache2-foreground"]
