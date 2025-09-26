# Multi-stage build for production optimization
FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Development stage
FROM base AS development

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy PHP configuration for development
COPY docker/php/php.dev.ini /usr/local/etc/php/php.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-interaction --no-scripts --no-autoloader

# Generate autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

EXPOSE 9000

CMD ["php-fpm"]

# Production stage
FROM base AS production

# Copy PHP configuration for production
COPY docker/php/php.prod.ini /usr/local/etc/php/php.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application files
COPY . .

# Install production dependencies
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 777 /var/www/html/storage/logs \
    && chmod -R 777 /var/www/html/storage/cache

# Create nginx run directory
RUN mkdir -p /run/nginx

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

