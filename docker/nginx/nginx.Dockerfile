# Базовый образ - официальный nginx на Alpine Linux
FROM nginx:stable-alpine

# Добавляем пользователя nginx в группу www-data
# Это нужно для совместимости с PHP-FPM, который обычно работает от имени www-data
RUN addgroup nginx www-data

# Копируем основной конфигурационный файл nginx
# Этот файл содержит глобальные настройки nginx (worker_processes, events и т.д.)
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf

# Копируем ВСЮ папку с конфигурациями виртуальных хостов
# Это заменяет все файлы в /etc/nginx/conf.d/, включая default.conf
COPY ./nginx/conf.d/ /etc/nginx/conf.d/

# Копируем статические файлы (HTML, CSS, JS) в директорию /app/public
# Директория /app создается автоматически Docker'ом при копировании
COPY ./public/ /app/public

# Копируем исходный код PHP в директорию /app/src
# Nginx будет передавать PHP-файлы на обработку PHP-FPM
COPY ./src/ /app/src

EXPOSE 80