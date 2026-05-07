# syntax=docker/dockerfile:1.6

# =========================================================
# STAGE 1: Base image with PHP 8.4 + all extensions
# We define this once and reuse it as the foundation for
# both the vendor stage and the production stage.
# =========================================================
FROM php:8.4-fpm-bookworm AS base

# Install OS packages needed for runtime AND for compiling extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    curl \
    git \
    unzip \
    supervisor \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libxslt1-dev \
    libsqlite3-dev \
    libmagickwand-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Configure & install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    exif \
    gd \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_sqlite \
    soap \
    sockets \
    xsl \
    zip

# Redis + Imagick via PECL
RUN pecl install redis imagick \
    && docker-php-ext-enable redis imagick

# Install Composer (copy the binary from the official Composer image)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer


# =========================================================
# STAGE 2: Build vendor with Composer (using our base image)
# Uses the same PHP 8.4 as production, so version matches.
# =========================================================
FROM base AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-autoloader \
    --no-interaction \
    --no-progress

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

# =========================================================
# STAGE 3: Build frontend assets with Node + Vite
# =========================================================
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files first for cache efficiency
COPY package.json package-lock.json* ./

RUN npm ci --no-audit --no-fund

# Copy the rest of the project (we need vite.config.js, resources/, etc.)
COPY . .

# Also need vendor/ for some Laravel-Vite plugins
COPY --from=vendor /app/vendor ./vendor

RUN npm run build


# =========================================================
# STAGE 4: Final production image
# =========================================================
FROM base AS production

# Set timezone
ENV TZ=Asia/Kolkata
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Copy our PHP/FPM/Supervisor configs
COPY docker/php/php.ini           /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini       /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf          /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/php/supervisord.conf  /etc/supervisor/conf.d/supervisord.conf

# Create non-root user matching host UID 1000
RUN groupadd -g 1000 app \
    && useradd -u 1000 -g app -m -s /bin/bash app

WORKDIR /var/www/html

# Copy the prepared app from the vendor stage
COPY --chown=app:app --from=vendor /app /var/www/html
# Copy compiled frontend assets from the frontend stage
COPY --chown=app:app --from=frontend /app/public/build /var/www/html/public/build

# Pre-create runtime dirs Laravel needs
RUN mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    && chown -R app:app storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

USER app

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]