FROM nginx:stable-alpine

# Добавляем пользователя nginx в группу www-data
RUN addgroup nginx www-data

COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/conf.d/ /etc/nginx/conf.d/
COPY ./public/ /app/public
COPY ./src/ /app/src

EXPOSE 80