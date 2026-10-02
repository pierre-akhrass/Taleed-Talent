# syntax=docker/dockerfile:1.7

FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
	COMPOSER_CACHE_DIR=/tmp/composer-cache composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

FROM php:8.4-cli
WORKDIR /app
RUN apt-get update \
	&& apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install pdo_mysql gd \
	&& rm -rf /var/lib/apt/lists/*
COPY --from=vendor /app/vendor ./vendor
COPY . .
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
