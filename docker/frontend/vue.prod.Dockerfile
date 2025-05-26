# docker/frontend/vue.prod.Dockerfile

### Stage 1 — сборка фронта
FROM node:22-alpine AS builder

WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
RUN npm run build

### Stage 2 — nginx
FROM nginx:stable-alpine

# Удаляем дефолтный конфиг
RUN rm /etc/nginx/conf.d/default.conf

# Добавляем наш конфиг
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

# Кладём собранный фронт в public root
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
