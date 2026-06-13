FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Prvo samo composer datoteke (bolji caching) pa instalacija
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist

# Pa ostatak koda
COPY . .

RUN composer dump-autoload --no-dev --optimize
RUN mkdir -p var && chmod -R 777 var

CMD php -S 0.0.0.0:${PORT:-8080} -t public
