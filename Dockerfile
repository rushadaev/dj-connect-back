FROM php:8.2-fpm

WORKDIR /var/www/dj-connect-back

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    libmagickwand-dev --no-install-recommends \
    && docker-php-ext-install pdo pdo_pgsql gd zip \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY dj-connect-back .

# Create .env file and set permissions
RUN touch .env && \
    chown www-data:www-data .env && \
    chmod 644 .env

# Set ownership of the application files
RUN chown -R www-data:www-data .

USER www-data

CMD ["php-fpm"]