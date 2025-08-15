# User Flow — Основные сценарии пользователя (MVP)

Версия: 1.0  
Дата: 2025-08-14

Документ описывает пользовательские сценарии именно этого учебного мини‑проекта. Основано на README.md, project-context.md и requirements.md.

Важно: шаги «регистрация» и «подписка/оплата» в рамках данного проекта находятся вне области (out of scope). В текущем MVP нет учётных записей и биллинга. Основные сценарии — проверка строки через веб‑интерфейс или публичный API, а также операторская проверка статуса Redis Cluster.

## 1) Веб‑пользователь: Проверка строки со скобками (Vue UI → API)

Кратко: пользователь открывает фронтенд Vue, вводит строку и получает статус: valid | invalid | empty | invalid_format. Звонки к API идут через Nginx proxy по пути /api/*, внутри бэкенда без префикса.

Доступ к UI:
- dev: http://localhost:5173 (Vite dev server)
- prod: http://IP:8001 (через nginx-proxy; всё приложение работает в контейнерах)

Эндпоинты: 
- POST /api/validate (внутренний маршрут POST /validate)

Диаграмма (flowchart):

```mermaid
flowchart TD
    A[Открыть UI dev 5173 или prod IP:8001] --> B[Ввести строку со скобками]
    B --> C[Нажать Проверить]
    C --> D{UI отправляет POST /api/validate\nТело JSON: поле string}
    D -->|200 OK| E{Статус}
    E -->|valid| F[Показать: valid]
    E -->|invalid| G[Показать: invalid]
    E -->|empty| H[Показать: empty]
    E -->|invalid_format| I[Показать: invalid_format]
    D -->|400 ошибка запроса| J[Показать: поле string обязательно и должно быть строкой]
```

Пограничные случаи:
- Пустая строка после trim → empty.
- Недопустимые символы или длина больше 30 → invalid_format.
- Несбалансированные скобки → invalid.

## 2) Интегратор API: Прямой вызов публичного эндпоинта через прокси

Кратко: внешнее приложение шлёт запрос через Nginx proxy на /api/validate. CORS разрешает GET, POST, OPTIONS; для POST требуется Content-Type: application/json.

Эндпоинты:
- POST /api/validate

Диаграмма (sequence):

```mermaid
sequenceDiagram
    participant Client as API Client
    participant Proxy as Nginx Proxy :8001
    participant Backend as PHP Backend Nginx+FPM

    Client->>Proxy: POST /api/validate\nHeaders: Content-Type application/json\nBody: string=(()())
    Proxy->>Backend: POST /validate remove /api prefix
    Backend-->>Proxy: 200 status valid\nили 400 status ... или error ...
    Proxy-->>Client: Проксирует ответ как есть
```

Ошибки запросов 400: отсутствует поле string или неверный тип.

## 3) Оператор/Разработчик: Проверка доступности Redis Cluster

Кратко: оператор проверяет статус кластера Redis, который хранит PHP‑сессии. Приложение продолжит работать при disconnected, но сессии будут недоступны или нестабильны.

Эндпоинты:
- GET /api/status (внутренний маршрут GET /status)

Диаграмма (flowchart):

```mermaid
flowchart LR
    K[Выполнить GET /api/status] --> L{Состояние кворума Redis}
    L -->|Кворум достигнут| M[Ответ: redis_cluster: connected]
    L -->|Кворума нет| N[Ответ: redis_cluster: disconnected]
```

Примечания:
- Health определяется числом доступных узлов и порогом кворума см. RedisHealthChecker.
- Логи и статус помогают диагностировать окружение dev и prod.

## 4) Сопоставление с типичным продуктовым флоу регистрация → подписка → тестирование → результаты

Для ясности: первые два шага не применимы к данному мини‑проекту. Ниже — иллюстрация, где отмечены вне scope шаги, а также показан фактический MVP‑путь.

Диаграмма (flowchart):

```mermaid
flowchart TD
    R[Регистрация пользователя]:::out --> S[Оформление подписки]:::out --> T[Тестирование] --> U[Результаты]

    subgraph Применимо в этом проекте MVP
      X[Открыть UI или вызвать API] --> Y[Передать строку из символов скобок]
      Y --> Z[Получить статус: valid, invalid, empty, invalid_format]
    end

    classDef out fill:#eee,stroke:#bbb,color:#999
```

Расшифровка:
- Регистрация и подписка — вне scope, нет аккаунтов и биллинга.
- Тестирование и Результаты — реализованы как проверка строки через API с отображением статуса.

## 5) Точки интеграции и маршрутизация

- Все внешние запросы идут через Nginx proxy по префиксу /api, который удаляется при проксировании. Во внутреннем приложении маршруты без /api.
- CORS: GET, POST, OPTIONS; заголовки Content-Type, Authorization. Preflight OPTIONS возвращает 200.
- Статусы валидации: valid | invalid | empty | invalid_format. Ошибка запроса — JSON с ключом error.message.

## 6) Ссылки

- README.md — запуск, структура, стек, API примеры.
- docs/overview/project-context.md — миссия, цели, ограничения, архитектура верхнего уровня.
- docs/overview/requirements.md — пользовательские истории, требования и DoD.
