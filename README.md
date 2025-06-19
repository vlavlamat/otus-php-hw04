# 🧹 Домашнее задание №5 — Валидация скобок и балансировка нагрузки

## 📦 Описание проекта

Учебный проект для отработки практических навыков в работе с Docker, PHP-FPM, Nginx, Redis Cluster и фронтендом на Vue.js.
Основная задача — реализовать веб-сервис, принимающий строку из скобок через POST-запрос, проверяющий её корректность с точки зрения вложенности и симметрии, а также организовать балансировку нагрузки между несколькими backend-контейнерами.

Проект построен на микросервисной архитектуре с разделением на:

✅ балансировщик (nginx upstream)
✅ два backend-а (nginx + php-fpm)
✅ Redis Cluster для хранения сессий и статистики
✅ frontend-приложение (Vue + Vite), предоставляющее веб-форму для проверки

## 🧱 Стек технологий

* PHP 8.4 (FPM)
* Nginx (балансировщик и backend)
* Redis Cluster (для сессий и статистики)
* Docker / Docker Compose
* Composer (PSR-4 autoload)
* Vue 3 + Vite + Axios

## 📁 Структура проекта

```
otus-php-hw04/
├── coverage/                      # Директория для отчетов о покрытии кода тестами
├── docker/
│   ├── backend/
│   │   └── backend.Dockerfile
│   ├── frontend/
│   │   ├── vue.dev.Dockerfile
│   │   └── vue.prod.Dockerfile
│   ├── php/
│   │   └── php.Dockerfile
│   └── proxy/
│       └── proxy.Dockerfile
├── frontend/
│   ├── src/
│   │   ├── utils/
│   │   │   └── bracketGenerator.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── index.html
│   ├── vite.config.js
│   └── package.json
├── nginx/
│   ├── backend/
│   │   ├── conf.d/
│   │   │   └── default.conf
│   │   └── nginx.conf
│   ├── frontend/
│   │   └── nginx.conf
│   └── proxy/
│       └── nginx.conf
├── php/
│   ├── conf.d/
│   │   └── session.redis.ini
│   ├── php.ini
│   ├── php-fpm.conf
│   └── www.conf
├── public/
│   └── index.php
├── src/
│   ├── RedisHealthChecker.php
│   ├── Router.php
│   └── Validator.php
├── tests/
│   ├── Unit/
│   │   └── ValidatorTest.php
│   ├── manual_test.php
│   └── redis_test.php
├── vendor/
├── .gitignore
├── composer.json
├── docker-compose.yml
├── docker-compose.dev.yml
├── docker-compose.prod.yml
├── Makefile
├── phpunit.xml                    # Конфигурация для PHPUnit тестов
└── README.md
```

## ⚙️ Как запустить проект

### Настройка переменных окружения

Перед запуском проекта необходимо настроить переменные окружения:

1. Для **dev-окружения**:
   - Скопируйте файл `.env.example` в `.env`
   - Отредактируйте `.env` файл, установив необходимые значения переменных

   ```bash
   cp .env.example .env
   # Отредактируйте .env файл
   ```

2. Для **prod-окружения**:
   - Скопируйте файл `.env.prod.example` в `.env.prod`
   - Отредактируйте `.env.prod` файл, установив необходимые значения переменных

   ```bash
   cp .env.prod.example .env.prod
   # Отредактируйте .env.prod файл
   ```

### Dev-режим (сборка и запуск всех сервисов с исходниками)

```bash
make dev-build    # Сборка и запуск
make dev-down     # Остановка dev-окружения
```

### Prod-режим (сборка и запуск production-образов)

```bash
make prod-up      # Подтянуть и запустить production-образы с использованием .env.prod
make prod-down    # Остановка prod-окружения
```

### Полный список команд в Makefile

* `make dev-up` — поднять dev-окружение
* `make dev-down` — остановить dev-окружение
* `make dev-build` — собрать и запустить dev-окружение с dev-зависимостями
* `make dev-rebuild` — пересобрать dev-окружение без кеша
* `make dev-logs` — показать dev-логи
* `make prod-up` — подтянуть образы и запустить prod-окружение с использованием .env.prod
* `make prod-down` — остановить prod-окружение
* `make prod-build` — собрать multi-arch prod-образы с использованием .env.prod и запушить в Docker Hub
* `make prod-logs` — показать prod-логи
* `make ps` — показать запущенные контейнеры
* `make test-setup` — подготовить окружение для тестирования
* `make test` — запустить PHPUnit тесты
* `make test-coverage` — запустить тесты с генерацией отчета о покрытии кода

## 🧪 Проверка работы

Всё тестируется **через веб-интерфейс**, сделанный на Vue.
Он доступен по адресу:
- В dev-режиме: [http://localhost](http://localhost)
- В prod-режиме: [http://localhost:8001](http://localhost:8001)

На странице отображается форма:
🔸 введите строку из скобок (или сгенерируйте случайный пример)
🔸 отправьте на проверку
🔸 получите результат прямо в браузере

Интерфейс также отображает статус подключения к Redis Cluster в нижней части страницы, обновляя его каждые 30 секунд.

В dev-режиме также доступен Vite dev-сервер на порту 5173.

## 🔍 Валидация строки

Валидация реализована в `src/Validator.php` и учитывает:

* пустые строки → ошибка
* корректность вложенности `(` и `)`
* запрет любых других символов

Результат обработки:

* ✅ 200 OK, если строка валидна
* ❌ 400 Bad Request, если строка некорректна

## 🌐 Архитектура Nginx

Балансировщик (балансирует запросы между backend и frontend):

```nginx
upstream backend_upstream {
    server nginx-backend1:80;
    server nginx-backend2:80;
}

upstream frontend_upstream {
    server frontend:80;
}

location / {
    proxy_pass http://frontend_upstream;
    proxy_http_version 1.1;
}

location /api/ {
    proxy_pass http://backend_upstream;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Backend-сервисы (nginx + php-fpm) используют Unix-сокеты для коммуникации между Nginx и PHP-FPM.

## 🛡️ Сессии и Redis Cluster

Сессии PHP сохраняются в Redis Cluster с помощью настроек:

```
session.save_handler = rediscluster
session.save_path = "seed[]=redis-node1:6379&seed[]=redis-node2:6379&seed[]=redis-node3:6379&seed[]=redis-node4:6379&seed[]=redis-node5:6379&seed[]=redis-node6:6379&seed[]=redis-node7:6379&seed[]=redis-node8:6379&seed[]=redis-node9:6379&seed[]=redis-node10:6379&prefix=otus_hw04:"
```

Кроме хранения сессий, Redis Cluster также используется для мониторинга состояния кластера через класс `RedisHealthChecker`.

## 🧪 Тестирование

Проект включает автоматизированные тесты для проверки корректности работы валидатора скобок и других компонентов:

* **Unit-тесты**: Проверяют корректность работы класса `Validator` с различными входными данными
* **Покрытие кода**: Генерация HTML-отчета о покрытии кода тестами

### Запуск тестов

```bash
make test            # Запуск всех тестов
make test-coverage   # Запуск тестов с генерацией отчета о покрытии
```

После выполнения `make test-coverage` отчет о покрытии будет доступен в директории `coverage/`.

## ✅ Выполненные требования

* [x] Docker-контейнеры: nginx, php-fpm, redis
* [x] POST-запрос `/api/validate`
* [x] Корректная валидация вложенных скобок
* [x] Балансировка между backend-ами через nginx upstream
* [x] Redis Cluster для сессий и статистики
* [x] Разделение dev и prod окружений
* [x] Frontend-интерфейс на Vue с генерацией тестовых данных
* [x] Сбор и хранение статистики валидаций
* [x] Мониторинг состояния Redis Cluster
* [x] Автоматизированные тесты с покрытием кода

## 🚀 Список улучшений для Best Practices

Вот подробный список того, как проект улучшен по сравнению с базовым домашним заданием:

### 🔄 **1. API Design: от Form-data к JSON REST API**
**ТЗ требовало:**
``` http
POST / HTTP/1.1
string=(()()()()))((((()()()))(()()()(((()))))))
```
**Реализовано:**
``` http
POST /api/validate HTTP/1.1
Content-Type: application/json
{"string": "(()()()()))((((()()()))(()()()(((()))))))"}
```
**Почему это лучше:**
- ✅ Современный REST API стандарт
- ✅ Структурированные данные вместо form-encoded
- ✅ Семантические URL endpoints (`/validate`, `/status`)
- ✅ Content-Type заголовки соответствуют данным

### 🏗️ **2. Архитектура: от монолита к микросервисам**
**ТЗ подразумевало:**
``` 
nginx → php-fpm → validation logic
```
**Реализовано:**
``` 
frontend (Vue.js) → balancer → backend (nginx+php) → redis cluster
```
**Почему это лучше:**
- ✅ Разделение frontend и backend
- ✅ Горизонтальное масштабирование (2 backend replica)
- ✅ Отказоустойчивость через балансировку
- ✅ Single Page Application вместо серверного рендеринга

### 📡 **3. HTTP Response Design: структурированные ответы**
**ТЗ требовало:**
``` 
200 OK - "всё хорошо"
400 Bad Request - "всё плохо"
```
**Реализовано:**
``` json
{
  "status": "success",
  "message": "Valid bracket sequence",
  "valid": true
}
```
**Почему это лучше:**
- ✅ Машиночитаемые ответы
- ✅ Структурированная обработка ошибок с кодами
- ✅ Метаданные для debugging (`error_code`, `field`)
- ✅ Consistent response format

### 🎨 **4. Frontend: от статики к реактивному UI**
**ТЗ не требовало frontend:**
**Реализовано:**
- Vue.js 3 с Composition API
- Реактивный интерфейс с валидацией
- Генератор тестовых данных
- Real-time мониторинг Redis статуса

**Почему это лучше:**
- ✅ Современный UX
- ✅ Интерактивное тестирование API
- ✅ Визуальная обратная связь
- ✅ Удобство для демонстрации проекта

### 🐳 **5. DevOps: профессиональная контейнеризация**
**ТЗ требовало:**
``` 
docker-compose с nginx + php-fpm
```
**Реализовано:**
- Отдельные dev/prod окружения
- Multi-architecture builds
- Makefile для автоматизации
- Готовые образы в Docker Hub

**Почему это лучше:**
- ✅ Production-ready deployment
- ✅ CI/CD готовность
- ✅ Reproducible builds
- ✅ Infrastructure as Code

### 🔒 **6. Обработка ошибок: от примитивов к исключениям**
**ТЗ запрещало:**
``` php
$result = @someFunction();
if (!$result) die('Error');
```
**Реализовано:**
``` php
try {
    $result = Validator::validate($string);
} catch (InvalidArgumentException $e) {
    // structured error handling
}
```
**Почему это лучше:**
- ✅ Типизированные исключения
- ✅ Proper error propagation
- ✅ Debugging-friendly stack traces
- ✅ Graceful degradation

### 🎛️ **7. Configuration Management**
**ТЗ не специфицировало:**
**Реализовано:**
- Environment-specific конфигурации
- Redis cluster настройки через ini
- Nginx оптимизации (gzip, caching, keepalive)
- PHP 8.4 strict typing

**Почему это лучше:**
- ✅ Environment parity (dev/prod)
- ✅ Performance optimizations
- ✅ Security best practices
- ✅ Maintainable configuration

### 📊 **8. Observability: мониторинг и логирование**
**ТЗ не требовало:**
**Реализовано:**
- Health check endpoint (`/status`)
- Структурированное логирование Nginx
- Redis cluster monitoring
- Server identification в ответах

**Почему это лучше:**
- ✅ Production monitoring
- ✅ Debugging capabilities
- ✅ Service discovery
- ✅ Infrastructure awareness

### 📝 **9. Code Quality: современные стандарты PHP**
**Базовый PHP в ТЗ:**
**Реализовано:**
``` php
declare(strict_types=1);
namespace App;
// PSR-4 autoloading
// Type hints everywhere
// PHPDoc documentation
```
**Почему это лучше:**
- ✅ Type safety
- ✅ PSR standards compliance
- ✅ IDE support
- ✅ Maintainable codebase

### 🧪 **10. Testing Infrastructure**
**ТЗ не требовало:**
**Реализовано:**
- PHPUnit интеграцию
- Test commands в Makefile
- Coverage reporting
- Отдельный test environment

**Почему это лучше:**
- ✅ Code quality assurance
- ✅ Regression prevention
- ✅ Continuous testing
- ✅ Documentation через тесты

---

## 📮 Автор

**Vladimir Matkovskii** — [vlavlamat@icloud.com](mailto:vlavlamat@icloud.com)
