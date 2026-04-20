FROM php:7.4-fpm-alpine

# Install dependencies
RUN apk add --no-cache curl libpng-dev libjpeg-turbo-dev freetype-dev bash mysql-client

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql json mbstring xml curl bcmath opcache

WORKDIR /app

COPY . /app

# Set permissions
RUN chown -R www-data:www-data /app && chmod -R 755 /app

# PHP configuration
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 9000

CMD ["php-fpm"]
