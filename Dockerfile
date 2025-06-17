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
    nodejs \
    npm \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd

# install composer
COPY --from=composer:2.8.9 /usr/bin/composer /usr/bin/composer

# Install dependencies
COPY . .

RUN composer install --no-dev --prefer-dist --optimize-autoloader

RUN php artisan optimize && \
    php artisan config:cache && \
    php artisan event:cache && \
    php artisan route:cache && \
    php artisan view:cache


RUN npm ci

RUN npm run build

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
COPY --from=build /var/www/html /var/www/html

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html

# Start Nginx server
EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
