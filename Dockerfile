FROM dunglas/frankenphp:php8.4

RUN install-php-extensions \
    gd \
    pdo_pgsql \
    intl \
    zip \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader --no-interaction --no-scripts

RUN mkdir -p bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views

RUN chmod -R 775 bootstrap/cache storage

RUN php artisan config:clear

CMD php artisan serve --host=0.0.0.0 --port=${PORT}
