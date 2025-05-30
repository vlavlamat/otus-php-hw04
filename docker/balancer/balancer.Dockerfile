FROM nginx:stable-alpine

RUN rm /etc/nginx/conf.d/default.conf
COPY ./balancer/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
