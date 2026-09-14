FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS unzip libonig-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
