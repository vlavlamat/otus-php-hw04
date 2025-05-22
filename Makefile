# Указываем, что эти цели (targets) не являются файлами,
# а просто именованными действиями, которые всегда должны выполняться.
.PHONY: up down build dev-up dev-down dev-build prod-up prod-down prod-build logs

# ────────────────────────────────
# Основные псевдонимы
# ────────────────────────────────

# Просто "make up" вызывает dev-up (по умолчанию: локальное dev-окружение)
up: dev-up

# "make down" выключает dev-окружение
down: dev-down

# "make build" собирает dev-окружение
build: dev-build

# ────────────────────────────────
# Dev окружение (локальная разработка)
# ────────────────────────────────

# Поднимаем окружение для разработки
dev-up:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Останавливаем окружение для разработки
dev-down:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down

# Собираем и поднимаем dev-окружение с флагом --build
dev-build:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build -d

# ────────────────────────────────
# Production окружение
# ────────────────────────────────

# Поднимаем production окружение
prod-up:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Останавливаем production окружение
prod-down:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml down

# Собираем и запускаем production окружение с билдом
prod-build:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up --build -d

# ────────────────────────────────
# Утилита
# ────────────────────────────────

# Просмотр логов всех сервисов (можно задать фильтр)
logs:
	docker compose logs -f --tail=100
