FROM node:24-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .
ARG INSTALL_DEV_DEPENDENCIES=false
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer install --no-interaction --optimize-autoloader; \
    else \
        composer install --no-dev --no-interaction --optimize-autoloader; \
    fi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY --from=frontend /app/public/build ./public/build

EXPOSE 80
