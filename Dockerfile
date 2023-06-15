FROM webdevops/php-nginx:8.2-alpine

RUN apk add oniguruma-dev postgresql-dev libxml2-dev
RUN docker-php-ext-install bcmath ctype fileinfo mbstring pdo_pgsql xml

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV WEB_DOCUMENT_ROOT /app/public
ENV APP_ENV production
WORKDIR /app
COPY . .
COPY .env.production .env

RUN composer install --no-interaction --optimize-autoloader --no-dev
# Optimizing Configuration loading
RUN php artisan config:cache
# Optimizing Route loading
RUN php artisan route:cache
# Optimizing View loading
RUN php artisan view:cache
RUN php artisan storage:link
RUN php artisan l5-swagger:generate

RUN chown -R application:application .
