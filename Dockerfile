FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod

WORKDIR /app
COPY . .
RUN rm -rf vendor && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
RUN composer dump-autoload --no-dev --optimize --no-scripts
RUN test -f vendor/autoload_runtime.php || (echo "AUTOLOAD MISSING" && exit 1)
RUN mkdir -p var && chmod -R 777 var

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public"]