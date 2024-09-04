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
    && docker-php-ext-install pdo pdo_pgsql gd zip pcntl \
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

# Set permissions for the storage and cron.d directories
RUN chown -R www-data:www-data /var/www/dj-connect-back/storage && \
    chmod -R 775 /var/www/dj-connect-back/storage

RUN chown -R www-data:www-data /var/www/dj-connect-back/cron.d && \
    chmod -R 775 /var/www/dj-connect-back/cron.d

# Install Supercronic
ARG SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.2.29
ARG SUPERCRONIC=supercronic-linux-amd64
ARG SUPERCRONIC_SHA1SUM=cd48d45c4b10f3f0bfdd3a57d054cd05ac96812b
RUN curl -fsSLO "$SUPERCRONIC_URL/$SUPERCRONIC" \
    && echo "$SUPERCRONIC_SHA1SUM $SUPERCRONIC" | sha1sum -c - \
    && chmod +x "$SUPERCRONIC" \
    && mv "$SUPERCRONIC" "/usr/local/bin/supercronic"

# Ensure cron directory exists and set up cron
COPY cron-schedule.sh /usr/local/bin/cron-schedule.sh
RUN chmod +x /usr/local/bin/cron-schedule.sh

USER www-data

CMD ["php-fpm"]