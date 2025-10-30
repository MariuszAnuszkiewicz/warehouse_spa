FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    netcat-openbsd \
    git \
    curl \
    libonig-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    dos2unix \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY ./backend .

RUN git config --global --add safe.directory /var/www/html
RUN composer install

COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && dos2unix /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]