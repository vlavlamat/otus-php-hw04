# Оптимизированный план тестирования мини-приложения

Исходя из правильной архитектуры приложения и принципов **эффективного тестирования**, ниже представлен оптимальный план тестирования с акцентом на **вертикальные срезы** и **критически важную бизнес-логику**.

## 🏗️ Правильная структура приложения

### **Модули** (функциональные домены):
- **Validation Module** - валидация скобочных последовательностей
- **Redis Health Module** - мониторинг состояния Redis кластера
- **Core Module** - общая инфраструктура приложения

### **Слои** (технические уровни внутри каждого модуля):
- **HTTP Layer** - CORS, Response, Middleware
- **Controllers Layer** - ValidationController, RedisHealthController
- **Services Layer** - ValidationService, координация логики
- **Domain Layer** - валидаторы, бизнес-логика, модели
- **Infrastructure Layer** - Redis соединения, Bootstrap, Router

### **Вертикальные срезы:**
1. **Валидация скобочных последовательностей** (`POST /validate`)
2. **Проверка здоровья Redis** (`GET /status`)
3. **CORS/Preflight** (`OPTIONS` запросы)

## 📊 Оптимизированный план тестирования

### 1. **Feature тесты** (~60% усилий, максимальная отдача)

Feature-тесты проверяют **полные вертикальные срезы** end-to-end, покрывая все контракты неявно.

#### **1.1. Валидация скобочных последовательностей**
```php
// tests/Feature/ValidationFeatureTest.php
```


**📋 HTTP → Controller → Service → Validators → Response контракт**

- ✅ `POST /validate` с валидными скобками `"(())"` → 200 + `{"status": "valid"}`
- ✅ `POST /validate` с невалидными скобками `"(()"` → 400 + `{"status": "invalid"}`
- ✅ `POST /validate` с неверным форматом `"(a)"` → 400 + `{"status": "invalid_format"}`
- ✅ `POST /validate` с пустой строкой `""` → 400 + `{"status": "empty"}`
- ✅ `POST /validate` без поля `string` → 400 + `{"error": {"message": "..."}}`
- ✅ `POST /validate` с неверным типом поля → 400 + error message
- ✅ `POST /validate` с невалидным JSON → 400 + JSON parse error
- ✅ Проверка заголовков: `Content-Type: application/json`

#### **1.2. Проверка здоровья Redis**
```php
// tests/Feature/RedisHealthFeatureTest.php
```


**📋 HTTP → Controller → HealthChecker → Redis контракт**

- ✅ `GET /status` при доступном Redis → 200 + `{"redis_cluster": "connected"}`
- ✅ `GET /status` при недоступном Redis → 200 + `{"redis_cluster": "disconnected"}`
- ✅ Проверка заголовков и структуры JSON
- ✅ Обработка исключений из RedisHealthChecker

#### **1.3. CORS/Preflight обработка**
```php
// tests/Feature/CorsFeatureTest.php
```


**📋 HTTP → CORS Middleware → Preflight Response контракт**

- ✅ `OPTIONS /validate` → 200 + CORS заголовки
- ✅ `OPTIONS /status` → 200 + CORS заголовки
- ✅ Preflight с `Access-Control-Request-Method` → корректные заголовки
- ✅ Обычные POST/GET запросы → CORS заголовки в ответе
- ✅ Проверка конкретных заголовков: `Access-Control-Allow-Origin`, `Access-Control-Allow-Methods`

### 2. **Unit тесты** (~30% усилий, сложная бизнес-логика)

Unit-тесты покрывают **только критически важную бизнес-логику** с высокой сложностью.

#### **2.1. Доменные валидаторы (алгоритмы)**
```php
// tests/Unit/Domain/BracketValidatorTest.php
```


**🎯 Алгоритм баланса скобок (однопроходный)**

- ✅ Сбалансированные: `"()"`, `"(())"`, `"((()))"`, `"()()"`
- ✅ Несбалансированные: `"("`, `")"`, `"(()"`, `"())"`
- ✅ Ранняя ошибка: `")("` → немедленный invalid
- ✅ Пустая строка → обработка edge case
- ✅ Сложные вложенные: `"((())())"` → valid
- 📊 **Data Provider** для массовой проверки паттернов

```php
// tests/Unit/Domain/FormatValidatorTest.php
```


**🎯 Валидация формата и нормализация**

- ✅ Валидные скобки: `"()"`, `"(())"`, `"()()"`
- ✅ Невалидный формат: `"abc"`, `"(a)"`, `"123"`, `"!@#"`
- ✅ Превышение максимальной длины (>30 символов) → `invalid_format`
- ✅ Пустая строка после trim: `""`, `"   "` → `empty`
- ✅ Граничные случаи: пробелы в начале/конце → правильная нормализация
- ✅ Unicode символы: `"()🚀"` → `invalid_format`
- 📊 **Data Provider** для edge cases

#### **2.2. Доменные модели (Value Objects)**
```php
// tests/Unit/Models/ValidationResultTest.php
```


**🎯 Типизированные фабричные методы и предикаты**

- ✅ Фабричные методы: `valid()`, `invalid()`, `empty()`, `invalidFormat()`
- ✅ Предикаты: `isValid()`, `isEmpty()`, `isInvalidFormat()`
- ✅ Проверка неизменяемости (immutability): свойства readonly
- ✅ Граничные случаи: все комбинации статусов создают разные состояния
- ✅ Корректность состояний: взаимоисключающие предикаты

### 3. **Integration тесты** (~10% усилий, внешние зависимости)

Integration-тесты проверяют взаимодействие с **реальными внешними системами**.

#### **3.1. Redis с реальным соединением**
```php
// tests/Integration/RedisHealthIntegrationTest.php
// @group integration (запуск только при наличии Docker)
```


**🔗 Реальная интеграция с Redis Cluster**

- ✅ Подключение к реальному Redis Cluster → `"connected"`
- ✅ Недоступный Redis (порт закрыт) → `"disconnected"`
- ✅ Частичное подключение (часть узлов недоступна) → проверка кворума
- ✅ Timeout при подключении → graceful handling
- ✅ Исключения RedisClusterException → правильная обработка
- ✅ Валидация конфигурации кластера
- 🐳 **Требует**: Docker-контейнер с Redis для CI/CD

## 🗂️ Финальная структура тестов

```
tests/
├── Feature/                                          # ~60% усилий
│   ├── ValidationFeatureTest.php                     # POST /validate (8 тестов)
│   ├── RedisHealthFeatureTest.php                    # GET /status (4 теста) 
│   └── CorsFeatureTest.php                           # OPTIONS /* (5 тестов)
├── Unit/                                             # ~30% усилий  
│   ├── Domain/
│   │   ├── BracketValidatorTest.php                  # Алгоритм баланса (6 тестов)
│   │   └── FormatValidatorTest.php                   # Валидация формата (7 тестов)
│   └── Models/
│       └── ValidationResultTest.php                  # Value Object (5 тестов)
└── Integration/                                      # ~10% усилий
    └── RedisHealthIntegrationTest.php                # Реальный Redis (6 тестов)
```


**Итого: ~40 тестов** вместо 100+ в избыточном подходе.

## ⚙️ Конфигурация запуска

```xml
<!-- phpunit.xml -->
<phpunit>
    <groups>
        <exclude>
            <group>integration</group>
        </exclude>
    </groups>
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```


**Команды запуска:**
- `vendor/bin/phpunit` - Feature + Unit (без внешних зависимостей)
- `vendor/bin/phpunit --group integration` - только Redis integration
- `vendor/bin/phpunit tests/Feature` - только end-to-end сценарии
- `vendor/bin/phpunit tests/Unit` - только бизнес-логика

## 🎯 Ключевые принципы

### ✅ **Что покрываем:**
1. **Все вертикальные срезы** через Feature-тесты
2. **Сложные алгоритмы** через Unit-тесты
3. **Внешние интеграции** через Integration-тесты

### ❌ **Что НЕ тестируем отдельно:**
- Простые DTO классы (`JsonResponse`, `PreflightResponse`)
- Координаторы без логики (`ValidationController`, `App`)
- HTTP инфраструктуру (`ResponseSender`) - покрывается в Feature
- Router и ExceptionHandler - покрывается в Feature

### 📊 **Ожидаемые результаты:**
- **~95% покрытия** критических путей
- **~85% покрытия** кода (line coverage)
- **3-5 секунд** время выполнения основного набора
- **Высокая скорость разработки** - меньше хрупких тестов
- **Легкое сопровождение** - понятная структура

## 🚀 План реализации

### **День 1: Feature Tests (приоритет #1)**
- ValidationFeatureTest.php - основная ценность
- RedisHealthFeatureTest.php - мониторинг
- CorsFeatureTest.php - интеграция с фронтендом

### **День 2: Unit Tests (бизнес-логика)**
- BracketValidatorTest.php - ядро алгоритма
- FormatValidatorTest.php - валидация входов
- ValidationResultTest.php - типобезопасность

### **День 3: Integration Tests (опционально)**
- RedisHealthIntegrationTest.php - только при наличии Docker

Этот план обеспечивает **максимальную уверенность** в коде при **минимальных затратах** на создание и поддержку тестов.


##  Почему именно эти 3 вертикальных среза?

### **Критерии выбора вертикального среза:**
1. ✅ **Сквозной** - проходит через все слои системы
2. ✅ **Ценный** - даёт конкретную пользу пользователю/системе
3. ✅ **Тестируемый** - имеет чёткие входы/выходы
4. ✅ **Независимый** - может работать изолированно

##  Анализ выбранных срезов

### **1. Валидация скобочных последовательностей (`POST /validate`)**

**Почему это вертикальный срез:**
```
HTTP запрос → ValidationController → ValidationService → 
FormatValidator → BracketValidator → ValidationResult → 
JsonResponse → HTTP ответ
```


✅ **Сквозной**: HTTP → Controllers → Services → Domain → Models → HTTP  
✅ **Ценный**: Основная бизнес-функция приложения  
✅ **Тестируемый**: Чёткие входы/выходы, разные сценарии  
✅ **Независимый**: Работает без других модулей

### **2. Проверка здоровья Redis (`GET /status`)**

**Почему это вертикальный срез:**
```
HTTP запрос → RedisHealthController → RedisHealthChecker → 
Redis Cluster → статус → JsonResponse → HTTP ответ
```


✅ **Сквозной**: HTTP → Controllers → Domain → Infrastructure → HTTP  
✅ **Ценный**: Мониторинг критической инфраструктуры  
✅ **Тестируемый**: connected/disconnected статусы  
✅ **Независимый**: Отдельная функциональность

### **3. CORS/Preflight (`OPTIONS` запросы)**

**Почему это вертикальный срез:**
```
OPTIONS запрос → CorsMiddleware → isPreflight() → 
PreflightResponse → CORS заголовки → HTTP ответ
```


✅ **Сквозной**: HTTP → Middleware → Response → HTTP  
✅ **Ценный**: Обеспечивает работу фронтенда  
✅ **Тестируемый**: Наличие/отсутствие заголовков  
✅ **Независимый**: Отдельная техническая функция

##  Это ВСЕ вертикальные срезы в приложении?

### **ДА, это все основные вертикальные срезы, потому что:**

1. **По функциональности** - приложение решает только 2 задачи:
    - Валидация скобок (основная)
    - Мониторинг Redis (вспомогательная)

2. **По техническим требованиям** - нужна поддержка CORS для фронтенда

3. **Размер приложения** - это мини-приложение с ограниченным scope

##  Что НЕ является вертикальными срезами

### **Горизонтальные слои (НЕ срезы):**
- ❌ "HTTP инфраструктура" - JsonResponse, ResponseSender
- ❌ "Core компоненты" - App, Router, ExceptionHandler
- ❌ "Bootstrap процесс" - EnvironmentLoader
- ❌ "Обработка ошибок" - глобальная обработка исключений

**Почему НЕ срезы:** Не дают **ценности пользователю** сами по себе, это технические слои.

##  Могли бы быть другие срезы?

### **Потенциальные дополнительные срезы:**

#### **4. Обработка некорректного JSON (`POST /validate`)**
```
Некорректный JSON → JsonException → ExceptionHandler → 
error response → HTTP ответ
```


**Но это НЕ отдельный срез**, потому что:
- Не имеет самостоятельной ценности
- Является частью среза "Валидация скобок"
- Это error-handling, а не основная функция

#### **5. Глобальная обработка ошибок (500, 404)**
```
Любое исключение → ExceptionHandler → fallback response → HTTP ответ
```


**Но это НЕ отдельный срез**, потому что:
- Это инфраструктурный слой
- Поддерживает другие срезы, но не создаёт ценность сам

## ✅ Заключение

**Да, эти 3 среза покрывают ВСЮ функциональность** приложения:

1. **Validation Slice** - основная бизнес-ценность
2. **Redis Health Slice** - мониторинг инфраструктуры
3. **CORS Slice** - техническая поддержка фронтенда

Больше вертикальных срезов в данном мини-приложении нет, потому что:
- ✅ Все пользовательские сценарии покрыты
- ✅ Все endpoints имеют свои срезы
- ✅ Scope приложения ограничен и фокусированный

Это правильная **полная декомпозиция** для данного проекта!
