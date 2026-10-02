FROM php:8.3-fpm

# System dependencies and PHP extensions required by Laravel 13
# (pdo_mysql, redis, gd, zip, intl, bcmath ... theo mục 0 & 2.2 của SPEC)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY backend/ ./
RUN composer dump-autoload --optimize --no-interaction

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

RUN groupadd -g 1000 laravel || true \
    && useradd -u 1000 -ms /bin/bash -g laravel laravel || true

EXPOSE 9000

# Entrypoint validates configuration, migrates the database and warms caches.
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
