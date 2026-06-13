FROM php:8.2-apache

# Sistemske ovisnosti + PHP ekstenzije
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl \
    && a2enmod rewrite

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache document root -> public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Kopiraj projekt
COPY . .

# Instaliraj ovisnosti (prod, bez dev paketa)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Dozvole za cache i logove
RUN chown -R www-data:www-data var

# Apache na Railwayev $PORT
RUN sed -ri -e 's!80!${PORT}!g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf
EXPOSE ${PORT}

CMD ["apache2-foreground"]