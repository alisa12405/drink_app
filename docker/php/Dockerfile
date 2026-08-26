FROM php:8.3-fpm

# System dependencies + PHP extensions required by Laravel 11
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
    default-mysql-client \
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

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN groupadd -g 1000 laravel || true \
    && useradd -u 1000 -ms /bin/bash -g laravel laravel || true

EXPOSE 9000

# entrypoint tu cai Laravel (neu chua co) + dong bo .env + sinh khung
# Models/Controllers/Services/Jobs truoc khi chay php-fpm (xem entrypoint.sh)
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
