# 🏗️ Backend Architecture — Техническая детализация (для учебного мини‑проекта)

Версия: 1.0
Дата: 2025‑08‑14

Документ описывает архитектуру backend части именно этого мини‑проекта «Валидация скобок». Цель — дать практическую, краткую и точную картину модулей, слоёв, middleware, жизненного цикла запроса и ключевых классов. Синхронизировано с README.md, docs/overview/* и реальным кодом в каталоге src/.

## 1) Модули и их ответственность

- Validation (валидация скобок)
  - Контроллер: App\Controllers\ValidationController
  - Сервис приложения: App\Services\ValidationService
  - Валидаторы (доменная логика):
    - App\Validator\FormatValidator — trim, пустота, допустимые символы "(" и ")", ограничение длины (≤ 30)
    - App\Validator\BracketValidator — проверка баланса скобок (односканерный алгоритм)
  - Модель-DTO: App\Models\ValidationResult — неизменяемый Value Object со статусами: valid, invalid, empty, invalid_format

- Health (инфраструктурное здоровье Redis Cluster)
  - Контроллер: App\Controllers\RedisHealthController
  - Сервис инфраструктуры: App\Redis\Health\RedisHealthChecker — подсчёт доступных узлов и сравнение с кворумом
  - Конфигурация: config/redis.php (узлы, кворум, таймауты, префикс и др.)

- HTTP/Core (каркас приложения)
  - Точка входа: public/index.php
  - Bootstrap: App\Bootstrap\EnvironmentLoader — загрузка .env и базовых настроек
  - Каркас/ядро: App\Core\App — инициализация, регистрация middleware и маршрутов, запуск
  - Роутер: App\Core\Router — таблица маршрутов, диспатч контроллеров по методу/пути
  - Исключения: App\Core\ExceptionHandler — централизованная обработка исключений → стандартизованные JSON‑ответы
  - HTTP слой: App\Http\JsonResponse, App\Http\PreflightResponse, App\Http\ResponseSender
  - Middleware: App\Http\Middleware\CorsMiddleware — CORS и обработка OPTIONS

## 2) Слои архитектуры (зависимости снизу вверх)

- Domain Core
  - Value Object: App\Models\ValidationResult (чистый, без инфраструктурных зависимостей)
  - Доменные алгоритмы: App\Validator\FormatValidator, App\Validator\BracketValidator

- Application Services
  - App\Services\ValidationService — оркестрация валидации: сперва формат, затем баланс; возвращает ValidationResult
  - App\Redis\Health\RedisHealthChecker — инфраструктурная проверка, используется в контроллере статуса

- Presentation (HTTP)
  - Контроллеры: ValidationController, RedisHealthController — преобразуют вход/выход HTTP ↔ доменные объекты/DTO
  - Ответы: JsonResponse, PreflightResponse, отправка через ResponseSender

- Infrastructure
  - EnvironmentLoader, CorsMiddleware, конфигурация Redis (config/redis.php)

- Framework/Core
  - App, Router, ExceptionHandler

## 3) Жизненный цикл HTTP‑запроса

1. Запрос приходит на Nginx proxy по пути /api/* и проксируется к backend, префикс /api снимается.
2. В public/index.php создаётся App, загружается окружение (EnvironmentLoader), регистрируются маршруты и middleware.
3. Router сопоставляет метод/путь и формирует обработчик контроллера. До контроллера вызываются глобальные middleware:
   - CorsMiddleware: устанавливает заголовки CORS; если метод OPTIONS — немедленно возвращает PreflightResponse (200).
4. Контроллер обрабатывает запрос:
   - ValidationController: читает JSON, валидирует наличие и тип поля string; вызывает ValidationService → получает ValidationResult → формирует JsonResponse со статусом и кодом (200 для valid, иначе 400 для empty/invalid_format/invalid). Ошибки запроса (нет поля/не строка/невалидный JSON) → 400 { error.message }.
   - RedisHealthController: вызывает RedisHealthChecker → { redis_cluster: connected|disconnected } (всегда 200).
5. Ответ отправляется через ResponseSender. Необработанные исключения перехватываются ExceptionHandler и переводятся в предсказуемые JSON‑ошибки.

## 4) Маршруты (внутренние пути в приложении)

- POST /validate → App\Controllers\ValidationController::validate
- GET  /status   → App\Controllers\RedisHealthController::status

Примечание: внешние клиенты обращаются к POST /api/validate и GET /api/status — префикс /api удаляется прокси.

## 5) Middleware и CORS

- App\Http\Middleware\CorsMiddleware
  - Разрешённые методы: GET, POST, OPTIONS
  - Разрешённые заголовки: Content-Type, Authorization
  - Для OPTIONS возвращает 200 PreflightResponse с нужными заголовками
  - Для остальных методов добавляет CORS‑заголовки к JsonResponse

## 6) Валидация и статусы

- Порядок: ValidationService → FormatValidator → BracketValidator
- Правила формата (FormatValidator): trim; пустая строка → status=empty (400);
  допустимы только "(" и ")"; длина ≤ 30; нарушение правил → status=invalid_format (400)
- Баланс (BracketValidator): односканерный алгоритм → status=valid (200) или invalid (400)
- DTO: ValidationResult (readonly; статические конструкторы для статусов)

## 7) Обработка ошибок

- JSON‑парсер/ошибка запроса (нет поля string или тип не строка) → 400 { error: { message: "Поле \"string\" обязательно и должно быть строкой" } }
- Неожиданные исключения → 500 { error: { message: "Internal Server Error" } } (точный формат определяется ExceptionHandler)
- Все ответы стандартизованы через JsonResponse/ResponseSender; preflight всегда 200

## 8) Конфигурация и окружение

- .env.*: переменные окружения для dev/prod (см. env/.env.*.example)
- App\Bootstrap\EnvironmentLoader: загрузка env и настройка PHP окружения
- config/redis.php: список узлов кластера, кворум, таймауты, префикс сессий
- PHP сессии: php/conf.d/session.redis.ini указывает session.save_handler=rediscluster и session.save_path с seed[] узлов

## 9) Тестирование (применительно к слоям)

- Unit: алгоритмы FormatValidator и BracketValidator, DTO ValidationResult
- Integration: контроллеры через роутер (POST /validate, GET /status), preflight OPTIONS
- Покрытие: make test-coverage → ./coverage (PCOV в dev)

## 10) Соответствие принципам (микро‑DDD / Clean)

- Чистое доменное ядро (DTO + алгоритмы) без инфраструктурных зависимостей
- Оркестрация в Application Service; контроллеры тонкие
- Инфраструктурные детали (CORS, env, Redis) отделены от домена
- Простая инверсия зависимостей через явные создания (без контейнера DI — для простоты учебного проекта)

## 11) Возможные улучшения (опционально)

- Service Container: простой контейнер для биндингов интерфейсов/реализаций
- Form Request объект для валидации входного JSON
- Логгер и корелляция запросов (request id) в JsonResponse
- Rate limiting middleware на уровне backend (или посредством Nginx)
- Repository слой — если появится БД и необходимость хранить историю проверок

## 12) Соответствие исходному коду (map → src/)

- src/Controllers: ValidationController, RedisHealthController
- src/Services: ValidationService
- src/Validator: FormatValidator, BracketValidator
- src/Models: ValidationResult
- src/Redis/Health: RedisHealthChecker
- src/Http: JsonResponse, PreflightResponse, ResponseSender, Middleware/CorsMiddleware
- src/Core: App, Router, ExceptionHandler
- src/Bootstrap: EnvironmentLoader

Эта архитектура отражает текущую реализацию проекта и ограничена учебным MVP: база данных, аутентификация, платёжные интеграции и сложная наблюдаемость не входят в состав.