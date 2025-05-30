# Указываем, что эти цели (targets) не являются файлами,
# а просто именованными действиями, которые всегда должны выполняться.
.PHONY: up down build dev-up dev-down dev-build \
        prod-up prod-down prod-build-multiarch \
        logs dev-logs prod-logs ps

# ────────────────────────────────
# Переменные
# ────────────────────────────────

# Docker Hub username
REGISTRY_USER = vlavlamat

# ────────────────────────────────
# Основные псевдонимы
# ────────────────────────────────

up: dev-up
down: dev-down
build: dev-build
logs: dev-logs

# ──────────────────────────────────────
# Dev окружение (локальная разработка arm64)
# ──────────────────────────────────────

dev-up:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

dev-down:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down

dev-build:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml build \
	  --build-arg INSTALL_DEV=true
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

dev-rebuild:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

dev-logs:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml logs -f --tail=100

# ────────────────────────────────
# Production окружение (сервер)
# ────────────────────────────────

prod-up:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

prod-down:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml down

prod-logs:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f --tail=100

# ────────────────────────────────
# Multi-architecture билд и пуш (единственный продакшн путь)
# ────────────────────────────────

prod-build:
	docker buildx create --use || true
	docker buildx build --platform linux/amd64,linux/arm64 --push --build-arg INSTALL_DEV=false -f docker/php/php.Dockerfile -t $(REGISTRY_USER)/php-fpm-hw04:prod .
	docker buildx build --platform linux/amd64,linux/arm64 --push -f docker/nginx/nginx.Dockerfile -t $(REGISTRY_USER)/nginx-backend-hw04:prod .
	docker buildx build --platform linux/amd64,linux/arm64 --push -f docker/balancer/balancer.Dockerfile -t $(REGISTRY_USER)/balancer-hw04:prod .
	docker buildx build --platform linux/amd64,linux/arm64 --push -f docker/frontend/vue.prod.Dockerfile -t $(REGISTRY_USER)/vue-hw04:prod .

# ────────────────────────────────
# Утилиты
# ────────────────────────────────

# Список запущенных контейнеров и их статуса
ps:
	docker compose ps
