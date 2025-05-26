# Указываем, что эти цели (targets) не являются файлами,
# а просто именованными действиями, которые всегда должны выполняться.
.PHONY: up down build dev-up dev-down dev-build \
        prod-up prod-down prod-build-local prod-push \
        logs dev-logs prod-logs ps

# ────────────────────────────────
# Переменные
# ────────────────────────────────

# Docker Hub username (заменён на твой — vlavlamat)
REGISTRY_USER = vlavlamat

# ────────────────────────────────
# Основные псевдонимы
# ────────────────────────────────

up: dev-up
down: dev-down
build: dev-build
logs: dev-logs

# ────────────────────────────────
# Dev окружение (локальная разработка)
# ────────────────────────────────

# Поднимаем окружение для разработки
dev-up:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Останавливаем окружение для разработки
dev-down:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down

# Собираем dev-окружение с INSTALL_DEV=true (оставляем dev-зависимости)
dev-build:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml build \
	  --build-arg INSTALL_DEV=true
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Полностью пересобираем dev-окружение без кеша
# Используется, если были серьёзные изменения в Dockerfile или зависимостях
# Выполняет:
#   1. Полный build с --no-cache (игнорирование кеша)
#   2. Перезапуск dev-контейнеров
dev-rebuild:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Просмотр логов в dev-окружении
dev-logs:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml logs -f --tail=100

# ────────────────────────────────
# Production окружение (сервер)
# ────────────────────────────────

# Подтягиваем готовые образы из Docker Hub и запускаем
prod-pull-up:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml pull
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Запускаем прод-окружение
prod-up:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Останавливаем прод-окружение
prod-down:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml down

# Просмотр логов в прод-окружении
prod-logs:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f --tail=100

# ────────────────────────────────
# Production билд и пуш (локально или на CI)
# ────────────────────────────────

# Локально билдим prod-образы с INSTALL_DEV=false (без dev-зависимостей)
prod-build-local:
	docker build --build-arg INSTALL_DEV=false -f docker/php/php.Dockerfile -t $(REGISTRY_USER)/php-fpm:prod .
	docker build -f docker/nginx/nginx.Dockerfile -t $(REGISTRY_USER)/nginx-backend:prod .
	docker build -f docker/balancer/balancer.Dockerfile -t $(REGISTRY_USER)/balancer:prod .
	# для фронтенда используем отдельный prod Dockerfile
	docker build -f docker/frontend/vue.prod.Dockerfile -t $(REGISTRY_USER)/vue:prod .

# Пушим prod-образы в Docker Hub
prod-push:
	docker push $(REGISTRY_USER)/php-fpm:prod
    docker push $(REGISTRY_USER)/nginx-backend:prod
    docker push $(REGISTRY_USER)/balancer:prod
    docker push $(REGISTRY_USER)/vue:prod

# ────────────────────────────────
# Утилиты
# ────────────────────────────────

# Список запущенных контейнеров и их статуса
ps:
	docker compose ps
