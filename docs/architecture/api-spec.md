# API Specification — Учебный сервис валидации скобок

Версия: 1.0  
Дата: 2025-08-14  
Статус: соответствует текущему коду и документации (README.md, backend-architecture.md, overview/*)

Документ описывает публичный REST API учебного мини‑проекта «Валидация скобок». Проект минималистичен: без БД, без аутентификации, без биллинга. Доступно два эндпоинта и CORS preflight.

Смотри также:
- docs/architecture/backend-architecture.md — контроллеры, слои и жизненный цикл запроса.
- docs/overview/requirements.md — требования и DoD.
- docs/overview/architecture.md — high‑level схема и маршрутизация через proxy.

---

## 1. Общие положения

- Базовый URL (через прокси): `http://localhost:8001`
- Префикс API: все публичные вызовы идут по пути `/api/*` и проксируются к backend; внутри приложения префикс `/api` удаляется. 
  - Пример: публичный `POST /api/validate` → внутренний `POST /validate`.
- Формат данных: JSON UTF‑8. Запросы с телом — `Content-Type: application/json`.
- Ответы: JSON. Коды состояния HTTP указаны для каждого эндпоинта ниже.
- CORS: разрешены методы `GET, POST, OPTIONS`; разрешённые заголовки: `Content-Type, Authorization`. Preflight (OPTIONS) всегда возвращает 200 и необходимые заголовки.
- Аутентификация/авторизация: отсутствуют (вне scope данного проекта).
- Версионирование API: отсутствует, так как это учебный MVP. Рекомендуется префикс `/api` на уровне прокси.

### 1.1 Заголовки
- Обязательный для POST: `Content-Type: application/json`
- Ответы содержат CORS‑заголовки, устанавливаемые middleware на backend.

### 1.2 Кодировки и локаль
- Все строки — UTF‑8. Статусы доменной логики — фиксированные значения на английском: `valid`, `invalid`, `empty`, `invalid_format`.

### 1.3 Ошибки и формат ответов об ошибках
- Бизнес‑ошибки валидации возвращаются как `{"status": "invalid|empty|invalid_format"}` с кодом `400 Bad Request`.
- Ошибки запроса (неверный JSON, отсутствие поля или неверный тип) возвращаются как:
  ```json
  { "error": { "message": "Поле \"string\" обязательно и должно быть строкой" } }
  ```
  с кодом `400 Bad Request`.
- Неперехваченные ошибки сервера обрабатываются центральным ExceptionHandler и возвращаются как:
  ```json
  { "error": { "message": "Internal Server Error" } }
  ```
  с кодом `500 Internal Server Error`.

---

## 2. Эндпоинты

### 2.1 POST /api/validate

Проверка строки со скобками на корректность баланса.

- Внутренний маршрут: `POST /validate`
- Требуемые заголовки: `Content-Type: application/json`
- Тело запроса (JSON):
  ```json
  { "string": "(()())" }
  ```

Правила валидации (соответствуют FormatValidator и BracketValidator):
- Поле `string` обязательно и должно быть строкой; иначе — ошибка запроса (см. ниже, формат `{ error.message }`).
- Перед проверкой применяется `trim`.
- Допустимы только символы `(` и `)`.
- Максимальная длина строки — 30 символов.
- Алгоритм баланса — односканерный. Итоговые статусы:
  - `valid` — корректная скобочная последовательность → 200 OK
  - `invalid` — некорректный баланс → 400 Bad Request
  - `empty` — пустая строка после `trim` → 400 Bad Request
  - `invalid_format` — недопустимые символы или превышение длины → 400 Bad Request

Ответы:
- 200 OK
  ```json
  { "status": "valid" }
  ```
- 400 Bad Request (доменная логика)
  ```json
  { "status": "invalid" }
  ```
  или
  ```json
  { "status": "empty" }
  ```
  или
  ```json
  { "status": "invalid_format" }
  ```
- 400 Bad Request (ошибка запроса)
  ```json
  { "error": { "message": "Поле \"string\" обязательно и должно быть строкой" } }
  ```
- 500 Internal Server Error
  ```json
  { "error": { "message": "Internal Server Error" } }
  ```

Примеры использования (curl):
- Успех
  ```bash
  curl -s -X POST http://localhost:8001/api/validate \
    -H 'Content-Type: application/json' \
    -d '{"string":"()()"}'
  # { "status": "valid" }
  ```
- Невалидный баланс
  ```bash
  curl -s -X POST http://localhost:8001/api/validate \
    -H 'Content-Type: application/json' \
    -d '{"string":"(("}'
  # { "status": "invalid" }
  ```
- Пустая строка
  ```bash
  curl -s -X POST http://localhost:8001/api/validate \
    -H 'Content-Type: application/json' \
    -d '{"string":"   "}'
  # { "status": "empty" }
  ```
- Неверный формат (посторонние символы)
  ```bash
  curl -s -X POST http://localhost:8001/api/validate \
    -H 'Content-Type: application/json' \
    -d '{"string":"(a)"}'
  # { "status": "invalid_format" }
  ```
- Ошибка запроса (нет поля string)
  ```bash
  curl -s -X POST http://localhost:8001/api/validate \
    -H 'Content-Type: application/json' \
    -d '{}'
  # { "error": { "message": "Поле \"string\" обязательно и должно быть строкой" } }
  ```

Схема запроса/ответа (JSON Schema, упрощённо):
- Request:
  ```json
  {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "type": "object",
    "required": ["string"],
    "properties": {
      "string": { "type": "string" }
    },
    "additionalProperties": false
  }
  ```
- Response (success):
  ```json
  {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "type": "object",
    "properties": { "status": { "enum": ["valid"] } },
    "required": ["status"],
    "additionalProperties": false
  }
  ```
- Response (domain error):
  ```json
  {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "type": "object",
    "properties": { "status": { "enum": ["invalid", "empty", "invalid_format"] } },
    "required": ["status"],
    "additionalProperties": false
  }
  ```
- Response (request/server error):
  ```json
  {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "type": "object",
    "properties": {
      "error": {
        "type": "object",
        "properties": { "message": { "type": "string" } },
        "required": ["message"],
        "additionalProperties": true
      }
    },
    "required": ["error"],
    "additionalProperties": true
  }
  ```

---

### 2.2 GET /api/status

Статус доступности Redis Cluster, используемого для хранения PHP‑сессий и health‑проверок. Возвращает 200 OK всегда, значение — в теле.

- Внутренний маршрут: `GET /status`
- Тело запроса: отсутствует
- Ответы:
  - 200 OK
    ```json
    { "redis_cluster": "connected" }
    ```
    или
    ```json
    { "redis_cluster": "disconnected" }
    ```

Примечания:
- Значение определяется на основе подсчёта доступных узлов и порога кворума (см. RedisHealthChecker и config/redis.php).
- Даже при `disconnected` приложение продолжает отвечать, но хранение сессий будет недоступно/нестабильно.

Схема ответа (JSON Schema):
```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "type": "object",
  "properties": { "redis_cluster": { "enum": ["connected", "disconnected"] } },
  "required": ["redis_cluster"],
  "additionalProperties": false
}
```

---

### 2.3 OPTIONS /api/* (CORS Preflight)

Обработка CORS preflight запросов. Выполняется на backend в CorsMiddleware. Возвращает 200 OK.

- Внутренний маршрут: `OPTIONS /*`
- Ответ (типовой):
  - 200 OK, заголовки:
    - `Access-Control-Allow-Origin: *` (или значение согласно реализации)
    - `Access-Control-Allow-Methods: GET, POST, OPTIONS`
    - `Access-Control-Allow-Headers: Content-Type, Authorization`

---

## 3. Ограничения и лимиты

- Ограничение длины строки для валидации: ≤ 30 символов.
- Допустимые символы входной строки: только `(` и `)`.
- Размер тела запроса: небольшой (по умолчанию nginx/php-fpm настройки покрытия достаточно; специальных лимитов в проекте не определено).
- Rate limiting: отсутствует на уровне приложения (возможен на уровне Nginx по желанию, см. backend‑architecture.md раздел улучшений).

---

## 4. Семантика кодов ответа

- 200 OK — успешный запрос (валидный результат валидации; статус Redis возвращён).
- 400 Bad Request — клиентская ошибка:
  - Доменные статусы: `invalid`, `empty`, `invalid_format` (для POST /api/validate).
  - Ошибка запроса: неверный JSON, отсутствие/неверный тип поля `string`.
- 500 Internal Server Error — непредвиденная серверная ошибка (см. ExceptionHandler).

Примечание: Для эндпоинта `/api/status` ошибки инфраструктуры не переводятся в 5xx; всегда 200 + поле `redis_cluster`.

---

## 5. Примеры интеграции

- JavaScript (Axios):
  ```js
  import axios from 'axios';

  async function validateString(s) {
    const { data } = await axios.post('/api/validate', { string: s });
    return data; // { status: 'valid' } или { status: 'invalid' | 'empty' | 'invalid_format' }
  }

  async function getRedisStatus() {
    const { data } = await axios.get('/api/status');
    return data; // { redis_cluster: 'connected' | 'disconnected' }
  }
  ```

- Bash (curl): см. примеры в разделе /api/validate выше и:
  ```bash
  curl -s http://localhost:8001/api/status
  # { "redis_cluster": "connected" } | { "redis_cluster": "disconnected" }
  ```

---

## 6. OpenAPI (минимальный фрагмент)

Ниже — минимальный OpenAPI 3.1‑совместимый фрагмент, отражающий текущее API (может быть расширен при необходимости):

```yaml
openapi: 3.1.0
info:
  title: Bracket Validation API (MVP)
  version: "1.0"
servers:
  - url: http://localhost:8001
paths:
  /api/validate:
    post:
      summary: Validate bracket string
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [string]
              properties:
                string:
                  type: string
      responses:
        '200':
          description: Valid string
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    enum: [valid]
                required: [status]
        '400':
          description: Business or request error
          content:
            application/json:
              schema:
                oneOf:
                  - type: object
                    properties: { status: { enum: [invalid, empty, invalid_format] } }
                    required: [status]
                  - type: object
                    properties:
                      error:
                        type: object
                        properties: { message: { type: string } }
                        required: [message]
                    required: [error]
        '500':
          description: Internal Server Error
          content:
            application/json:
              schema:
                type: object
                properties:
                  error:
                    type: object
                    properties: { message: { type: string } }
                    required: [message]
                required: [error]
  /api/status:
    get:
      summary: Redis Cluster health status
      responses:
        '200':
          description: Health status
          content:
            application/json:
              schema:
                type: object
                properties:
                  redis_cluster:
                    enum: [connected, disconnected]
                required: [redis_cluster]
```

---

## 7. Соответствие реализации

- Контроллеры и маршруты:
  - `POST /validate` → `App\Controllers\ValidationController::validate`
  - `GET /status` → `App\Controllers\RedisHealthController::status`
- Доменная логика: `App\Services\ValidationService` orchestrates `FormatValidator` → `BracketValidator` → `App\Models\ValidationResult`.
- Инфраструктура: CORS в `App\Http\Middleware\CorsMiddleware`; Redis health в `App\Redis\Health\RedisHealthChecker`.

---

## 8. Изменения и расширения (на будущее)

- Введение версионирования (например, `/api/v1`).
- Добавление истории проверок и БД (потребует новых ресурсов и схем).
- Аутентификация/авторизация (например, Sanctum или собственные токены) — вне текущего scope.
- Rate limiting на уровне Nginx или backend middleware.
