FROM php:8.3-fpm-alpine

# Устанавливаем нужные пакеты
RUN apk add --no-cache unzip git curl zlib-dev $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# Удаляем мешающий конфиг
RUN rm -f \
    /usr/local/etc/php-fpm.conf.default \
    /usr/local/etc/php-fpm.d/www.conf.default \
    /usr/local/etc/php-fpm.d/zz-docker.conf

# Копируем настройки PHP
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/local.ini

# Копируем настройки PHP (общие и сессионные)
COPY ./docker/php/conf.d/ /usr/local/etc/php/conf.d/

# 👇 Используем переменную сборки для выбора FPM-конфига
ARG FPM_CONF=www.conf
COPY ./docker/php/${FPM_CONF} /usr/local/etc/php-fpm.d/www.conf

# Копируем бинарник Composer из официального образа
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем проект внутрь контейнера
COPY ./src /app/src
COPY ./public /app/public

WORKDIR /app

CMD ["php-fpm"]