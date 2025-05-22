# docker/balancer/balancer.Dockerfile

### Stage 1 — frontend build
FROM node:20-alpine AS frontend-builder

WORKDIR /app

COPY ../../frontend/package*.json ./
RUN npm install

COPY ../../frontend/ ./
RUN npm run build


### Stage 2 — nginx + frontend
FROM nginx:stable-alpine

RUN rm /etc/nginx/conf.d/default.conf
COPY ./balancer/nginx.conf /etc/nginx/conf.d/default.conf

# Копируем собранный фронтенд
COPY --from=frontend-builder /app/dist /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
