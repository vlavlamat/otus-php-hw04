# 🧹 Домашнее задание №4 — Валидация скобок и балансировка нагрузки

## 📦 Описание проекта

Учебный проект для практики Docker, PHP-FPM, Nginx, Redis Cluster и фронтенда на Vue.js.
Цель: веб‑сервис, который принимает строку со скобками через POST JSON, проверяет корректность последовательности и работает за балансировщиком с несколькими backend‑инстансами.

Сервисы:
- балансировщик (Nginx upstream)
- два backend-а (Nginx + PHP-FPM)
- Redis Cluster (сессии и мониторинг)
- frontend (Vue + Vite)

## 🧱 Текущий стек

- PHP 8.4 (FPM) + PCOV (dev)
- Nginx (proxy и backend)
- Redis Cluster (sessions + health)
- Docker / Docker Compose
- Composer (PSR-4 автозагрузка)
- Vue 3 + Vite + Axios

## 📁 Актуальная структура проекта (сокращенно)

```
otus-php-hw04/
├── config/
│   └── redis.php                  # Конфигурация Redis Cluster (узлы, кворум, таймауты)
├── docker/
│   ├── backend/backend.Dockerfile
│   ├── frontend/
│   │   ├── vue.dev.Dockerfile
│   │   └── vue.prod.Dockerfile
│   ├── php/php.Dockerfile
│   └── proxy/proxy.Dockerfile
├── env/
│   ├── .env.dev.example
│   └── .env.prod.example
├── frontend/                      # Исходники Vue приложения
├── nginx/
│   ├── backend/                   # Nginx для backend контейнеров
│   └── proxy/default.conf         # Балансировщик и маршрутизация
├── php/
│   ├── conf.d/session.redis.ini   # Сохранение сессий в Redis Cluster
│   ├── dev.php.ini                # Dev‑настройки PHP
│   └── prod.php.ini               # Prod‑настройки PHP
├── public/index.php               # Входная точка PHP‑приложения
├── src/
│   ├── Bootstrap/EnvironmentLoader.php
│   ├── Controllers/
│   │   ├── ValidationController.php
│   │   └── RedisHealthController.php
│   ├── Core/
│   │   ├── App.php
│   │   ├── ExceptionHandler.php
│   │   └── Router.php
│   ├── Http/
│   │   ├── JsonResponse.php
│   │   ├── PreflightResponse.php
│   │   ├── Middleware/CorsMiddleware.php
│   │   └── ResponseSender.php
│   ├── Models/ValidationResult.php
│   ├── Redis/Health/RedisHealthChecker.php
│   ├── Services/ValidationService.php
│   └── Validator/
│       ├── BracketValidator.php
│       └── FormatValidator.php
├── tests/                         # Unit и Integration тесты
├── docker-compose.yml             # Общая оркестрация (порт 8001)
├── docker-compose.dev.yml         # Dev‑overrides (Vite 5173)
├── docker-compose.prod.yml        # Prod‑overrides
├── Makefile
├── phpunit.xml
└── README.md
```

## ⚙️ Запуск

### 1) Переменные окружения

Скопируйте и отредактируйте шаблоны:

```bash
cp env/.env.dev.example env/.env.dev
cp env/.env.prod.example env/.env.prod
# затем отредактируйте env/.env.dev и env/.env.prod при необходимости
```

### 2) Dev‑режим (локальная разработка)

```bash
make dev-build    # сборка и запуск dev окружения
make dev-logs     # логи
make dev-down     # остановка
```

Приложение доступно через прокси: http://localhost:8001
Vite dev‑сервер фронтенда (прямой доступ): http://localhost:5173

### 3) Prod‑режим

```bash
make prod-up      # запуск образов в prod-конфигурации
make prod-logs    # логи
make prod-down    # остановка
```

По умолчанию балансировщик публикуется на http://localhost:8001

### 4) Полезные команды Makefile

- dev-up, dev-down, dev-build, dev-rebuild, dev-logs
- prod-up, prod-down, prod-logs, prod-build
- ps — список контейнеров
- test-setup, test, test-unit, test-integration, test-coverage

## 🌐 Внешние эндпоинты (через прокси)

Прокси (nginx/proxy/default.conf) проксирует /api/ к backend и удаляет префикс /api/ при проксировании, поэтому внутри приложения маршруты регистрируются без /api.

- POST /api/validate
  - Тело запроса (JSON): {"string": "(()())"}
  - Ответы:
    - 200 OK: {"status":"valid"}
    - 400 Bad Request: {"status":"empty"} | {"status":"invalid_format"} | {"status":"invalid"}
    - 400 Bad Request (ошибка запроса): {"error":{"message":"Поле \"string\" обязательно и должно быть строкой"}}

- GET /api/status
  - 200 OK: {"redis_cluster":"connected"} или {"redis_cluster":"disconnected"}

CORS обрабатывается в App\Http\Middleware\CorsMiddleware (GET, POST, OPTIONS; заголовки Content-Type, Authorization). Preflight OPTIONS возвращает 200.

## 🔍 Логика валидации

Пайплайн (App\Services\ValidationService):
- FormatValidator: трим строки, проверка пустоты, только символы '(' и ')', ограничение длины (по коду — 30), статус: valid | empty | invalid_format.
- BracketValidator: односканерная проверка баланса, статус: valid | invalid.
- DTO: App\Models\ValidationResult (статусы: valid, invalid, empty, invalid_format).

## 🛡️ Сессии и Redis Cluster

- Сессии PHP хранятся в Redis Cluster (php/conf.d/session.redis.ini):
  - session.save_handler = rediscluster
  - session.save_path = "seed[]=redis-node1:6379&...&seed[]=redis-node10:6379&prefix=otus_hw04:"
- Конфигурация кластера и мониторинга в config/redis.php (узлы, кворум, таймауты, префикс и параметры GC для сессий берутся из env).
- Health‑чекер: App\Redis\Health\RedisHealthChecker — подсчёт доступных узлов и определение connected/disconnected на основе кворума.

## 🧪 Тестирование

```bash
make test            # все тесты (в контейнере php-fpm1)
make test-unit       # только unit
make test-integration# только integration
make test-coverage   # html‑отчёт в ./coverage
```

## ✅ Выполнено

- [x] Балансировка между двумя backend (Nginx upstream)
- [x] POST JSON API /api/validate
- [x] Валидация формата и баланса скобок
- [x] Redis Cluster для сессий и health‑мониторинга
- [x] Разделение dev/prod окружений и PHP‑настроек
- [x] Vue 3 фронтенд + Vite (dev сервер)
- [x] Автотесты (Unit/Integration) и покрытие кода

## 📮 Автор

Vladimir Matkovskii — vlavlamat@icloud.com
