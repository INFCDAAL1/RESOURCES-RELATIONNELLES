FROM php:8.4.8-alpine3.22  AS build

WORKDIR /var/www/html

# installing system dependencies and php extensions
RUN apk add --no-cache \
    zip \
    libzip-dev \
    freetype \
    libjpeg-turbo \
    libpng \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd

# install composer
COPY --from=composer:2.8.9 /usr/bin/composer /usr/bin/composer

# Install dependencies
COPY composer.json composer.lock ./
COPY artisan ./artisan
COPY app ./app
COPY config ./config
COPY database ./database
COPY public ./public
COPY routes ./routes
COPY storage ./storage
COPY bootstrap ./bootstrap


RUN composer install --no-dev --prefer-dist --optimize-autoloader

FROM php:8.4.8-fpm-alpine3.22

LABEL org.opencontainers.image.source=https://github.com/INFCDAAL1/RESOURCES-RELATIONNELLES

# install nginx
RUN apk add --no-cache \
    zip \
    libzip-dev \
    freetype \
    libjpeg-turbo \
    libpng \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    oniguruma-dev \
    gettext-dev \
    freetype-dev \
    nginx \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && docker-php-ext-install bcmath \
    && docker-php-ext-enable bcmath \
    && docker-php-ext-install exif \
    && docker-php-ext-enable exif \
    && docker-php-ext-install gettext \
    && docker-php-ext-enable gettext \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable opcache \
    && rm -rf /var/cache/apk/*


# Copy the build output to the Nginx HTML directory
COPY --from=build /var/www/html/composer.json /var/www/html/composer.lock ./
COPY --from=build /var/www/html/artisan ./artisan
COPY --from=build /var/www/html/app ./app
COPY --from=build /var/www/html/config ./config
COPY --from=build /var/www/html/database ./database
COPY --from=build /var/www/html/public ./public
COPY --from=build /var/www/html/routes ./routes
COPY --from=build /var/www/html/storage ./storage
COPY --from=build /var/www/html/bootstrap ./bootstrap
COPY --from=build /var/www/html/vendor /var/www/html/vendor

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Configure PHP-FPM
RUN echo "upload_max_filesize=100M\npost_max_size=100M" > /usr/local/etc/php/conf.d/uploads.ini

# Donner les permissions appropriées
RUN chown -R www-data:www-data storage bootstrap/cache

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Start Nginx server
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
