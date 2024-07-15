FROM php:8.2-fpm

WORKDIR /var/www/dj-connect-back

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git

RUN docker-php-ext-install pdo pdo_pgsql gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY dj-connect-back .

# Create .env file and set permissions
RUN touch .env && \
    chown www-data:www-data .env && \
    chmod 644 .env

RUN chown -R www-data:www-data .
USER www-data

CMD ["php-fpm"]