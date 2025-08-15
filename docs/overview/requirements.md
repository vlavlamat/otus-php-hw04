# Требования — Учебный сервис валидации скобок

Версия: 1.0
Дата: 2025-08-14

Документ описывает требования к мини‑проекту по проверке скобочных последовательностей. Основано на README.md, docs/overview/project-context.md и текущей реализации кода.

## 1. Пользовательские истории

- Как пользователь веб‑интерфейса я хочу ввести строку со скобками и получить ответ о корректности, чтобы быстро проверить выражение. [MVP]
- Как интегратор API я хочу отправлять POST запрос с JSON на публичный endpoint и получать стандартизированные ответы, чтобы встроить проверку в свой сервис. [MVP]
- Как оператор/разработчик я хочу иметь endpoint статуса Redis Cluster, чтобы понимать доступность инфраструктуры хранения сессий. [MVP]
- Как разработчик я хочу запускать проект одной командой в dev/prod режимах и иметь автотесты и отчет о покрытии, чтобы быстро проверять изменения. [MVP]
- Как преподаватель/ревьюер я хочу прозрачные шаги запуска, понятную структуру проекта и минимальные, но достаточные требования по качеству, чтобы быстро оценить работу. [MVP]

Вне текущего MVP (на будущее): история проверок, аутентификация, роли, дизайн‑система, метрики Prometheus/Grafana, биллинг и т.п.

## 2. Функциональные требования (API)

Общие положения:
- Все внешние запросы идут через Nginx proxy по префиксу `/api`. При проксировании префикс удаляется, внутри приложения маршруты регистрируются без `/api`.
- Контент запросов к POST должен иметь заголовок `Content-Type: application/json`.
- Ответы — в формате JSON, с единым оформлением ошибок для неверных запросов.
- CORS: разрешены методы GET, POST, OPTIONS; заголовки Content-Type, Authorization. Preflight (OPTIONS) возвращает 200 с корректными заголовками.

### 2.1 POST /api/validate (внутренний маршрут: POST /validate)

Назначение: проверить строку на корректность с точки зрения баланса скобок.

Запрос:
- Заголовки: `Content-Type: application/json`
- Тело (JSON объект): `{ "string": "(()())" }`

Бизнес‑правила валидации:
- Поле `string` обязательно и должно быть строкой; иначе — ошибка запроса (см. ниже).
- Перед проверкой выполняется trim.
- Разрешены только символы `(` и `)`; любые другие символы — статус `invalid_format`.
- Максимальная длина строки — 30 символов; превышение — `invalid_format` (как часть правил формата).
- Баланс скобок проверяется односканерным алгоритмом: итоговые статусы — `valid` или `invalid`.

Ответы:
- 200 OK: `{ "status": "valid" }`
- 400 Bad Request: `{ "status": "empty" }` — после trim строка пустая.
- 400 Bad Request: `{ "status": "invalid_format" }` — присутствуют недопустимые символы и/или нарушено ограничение длины.
- 400 Bad Request: `{ "status": "invalid" }` — строка не сбалансирована по скобкам.
- 400 Bad Request (ошибка запроса): `{ "error": { "message": "Поле \"string\" обязательно и должно быть строкой" } }` — если поле отсутствует или имеет неверный тип.

Примеры:
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"()()"}'`
  → `{ "status": "valid" }`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"(("}'`
  → `{ "status": "invalid" }`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"   "}'`
  → `{ "status": "empty" }`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"(a)"}'`
  → `{ "status": "invalid_format" }`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{}'`
  → `{ "error": { "message": "Поле \"string\" обязательно и должно быть строкой" } }`

### 2.2 GET /api/status (внутренний маршрут: GET /status)

Назначение: предоставить статус доступности Redis Cluster, используемого для хранения PHP‑сессий и health‑проверок.

Ответы:
- 200 OK: `{ "redis_cluster": "connected" }`
- 200 OK: `{ "redis_cluster": "disconnected" }`

Примечания:
- Статус определяется на основе подсчета доступных узлов и порога кворума.
- Приложение должно корректно работать даже при `disconnected` (но хранение сессий будет недоступно/нестабильно).

### 2.3 OPTIONS /api/* (CORS Preflight)

Назначение: обработка CORS preflight запросов.

Ответ:
- 200 OK с заголовками CORS (разрешены методы GET, POST, OPTIONS; заголовки Content-Type, Authorization).

## 3. Нефункциональные требования

- Производительность:
  - Алгоритм проверки — O(n) при n ≤ 30; ожидаемая латентность локально < 50 мс на запрос.
  - Проект учебный, строгих SLA нет, но ответы должны быть стабильны при последовательной нагрузке через Nginx upstream.
- Масштабирование и отказоустойчивость:
  - Два backend‑инстанса за Nginx (round‑robin). Sticky‑sessions не используются.
  - Сессии хранятся в Redis Cluster; «здоровье» определяется по кворуму узлов.
  - Приложение стартует и отвечает даже при недоступном Redis (эндпойнт статуса отражает проблему).
- Безопасность:
  - Базовый CORS (GET, POST, OPTIONS; заголовки Content-Type, Authorization).
  - Жесткая валидация входных данных; строгая обработка JSON (исключение при неверном JSON).
  - Аутентификация/авторизация вне scope; HTTPS зависит от окружения и вне scope проекта.
- Качество и тестирование:
  - Юнит‑ и интеграционные тесты (PHPUnit). Все тесты должны проходить командой `make test`.
  - Отчет о покрытии кода в каталоге `./coverage` (PCOV в dev).
- Эксплуатация и деплой:
  - Полностью контейнеризовано (Docker / Docker Compose). Раздельные dev/prod конфигурации.
  - Переменные окружения в `env/.env.dev` и `env/.env.prod` на основе примеров.
  - Простые команды Makefile для сборки, запуска и логирования.
- Код и архитектура:
  - PHP 8.4, строгие типы (`declare(strict_types=1);`), PSR‑4 автозагрузка.
  - Слои: Controllers → Services → Validator/Models, простой Router, централизованный ExceptionHandler.

## 4. Критерии готовности MVP (Definition of Done)

Функциональные:
- [ ] POST /api/validate возвращает корректные статусы для кейсов: valid, invalid, empty, invalid_format, и корректное сообщение об ошибке при отсутствии/неверном типе `string`.
- [ ] GET /api/status возвращает `connected` при доступном кворуме узлов Redis и `disconnected` при недостаточном кворуме.
- [ ] CORS preflight (OPTIONS) для /api/* возвращает 200 с корректными заголовками; реальные запросы из браузера к фронтенду работают.

Инфраструктурные:
- [ ] Запуск dev окружения: `make dev-build` поднимает proxy, два backend‑инстанса, Redis Cluster и фронтенд (Vite); приложение доступно на `http://localhost:8001` (API) и `http://localhost:5173` (frontend dev server).
- [ ] Запуск prod окружения: `make prod-up` поднимает необходимые сервисы; API доступно через `http://localhost:8001`.
- [ ] Тесты: `make test` проходят успешно; `make test-coverage` формирует HTML отчет в `./coverage`.

Проверка через curl (пример):
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"()()"}' | grep '"valid"'`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"(("}' | grep '"invalid"'`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"   "}' | grep '"empty"'`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{"string":"(a)"}' | grep '"invalid_format"'`
- `curl -s -X POST http://localhost:8001/api/validate -H 'Content-Type: application/json' -d '{}' | grep '"error"'`
- `curl -s http://localhost:8001/api/status | grep -E 'connected|disconnected'`

## 5. Допущения и ограничения
- Строка входа — не более 30 символов, только `(` и `)`; прочие символы — `invalid_format`.
- Отсутствие sticky‑sessions; фронтенд/браузерные сессии опираются на Redis.
- Проект предназначен для локального/учебного использования; безопасность настроена минимально для демонстрации.

## 6. Вне области (Out of Scope)
- Аутентификация/авторизация, роли пользователей.
- Хранение истории проверок, профили пользователей.
- Платежи/подписки, админ‑панель.
- Расширенная наблюдаемость (метрики, трейсы) и алертинг.
- Многоязычность фронтенда, SEO и полноценная дизайн‑система.

## 7. Ссылки
- README.md — запуск, структура, стек, примеры API.
- docs/overview/project-context.md — миссия, цели, ограничения, архитектура верхнего уровня.
