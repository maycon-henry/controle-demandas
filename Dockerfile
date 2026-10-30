# Prazo Certo · imagem de produção (Render, runtime Docker)
FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev libpng-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip gd opcache \
    && a2enmod rewrite headers \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/prazocerto.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY --from=dependencias /app /var/www/html
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PORT=10000
EXPOSE 10000

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
