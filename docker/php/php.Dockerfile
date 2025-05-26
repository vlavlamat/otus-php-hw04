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
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY ./docker/php/conf.d/ /usr/local/etc/php/conf.d/

# 👇 Используем переменную сборки для выбора FPM-конфига
ARG FPM_CONF=www.conf
COPY ./docker/php/${FPM_CONF} /usr/local/etc/php-fpm.d/www.conf

# Копируем бинарник Composer из официального образа
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /app

# ⏩ Копируем composer файлы отдельно (для кеша)
COPY composer.json ./

# ⏩ Передаём аргумент для управления dev/prod-зависимостями
ARG INSTALL_DEV=false

# ⏩ Устанавливаем зависимости с условием
RUN if [ "$INSTALL_DEV" = "true" ]; then \
      composer install --no-interaction --prefer-dist --no-scripts; \
    else \
      composer install --no-interaction --prefer-dist --no-scripts --no-dev; \
    fi

# ⏩ Копируем оставшиеся файлы проекта
COPY ./src /app/src
COPY ./public /app/public

CMD ["php-fpm"]