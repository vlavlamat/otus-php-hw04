# Указываем, что эти цели (targets) не являются файлами,
# а просто именованными действиями, которые всегда должны выполняться.
.PHONY: prod-up prod-down prod-logs ps

# ────────────────────────────────
# Переменные
# ────────────────────────────────

# Docker Hub username
REGISTRY_USER = vlavlamat

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
# Утилиты
# ────────────────────────────────

# Список запущенных контейнеров и их статуса
ps:
	docker compose ps
