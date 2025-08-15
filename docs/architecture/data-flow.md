# Архитектура потоков данных (Data Flow Architecture)

---

## 🔹 API валидации скобочных последовательностей

### **Основной поток валидации**
```markdown
📥 **HTTP REQUEST:**
   ├─ Method: POST /api/validate
   ├─ Headers: Content-Type: application/json
   └─ Body: {"string": "(())"}
       ├─ string (required): любая строка, включая пустую
       └─ Ограничения: поле обязательно, должно быть строкой

🔄 **ИЗВЛЕЧЕНИЕ СЫРЫХ ДАННЫХ** (ValidationController::getRequestData):
   ├─ Input: Raw HTTP request body (binary stream)
   ├─ Операции:
   │   ├─ file_get_contents('php://input') → raw JSON string
   │   ├─ empty($input) → return []
   │   ├─ json_decode($input, true, 4, JSON_THROW_ON_ERROR) → mixed
   │   └─ is_array($data) ? $data : []
   ├─ Output: ["string" => "(())"] | []
   └─ Ошибки: JsonException → "Неверный формат JSON: {details}"

🔄 **ВАЛИДАЦИЯ СТРУКТУРЫ ЗАПРОСА** (ValidationController::handleValidationRequest):
   ├─ Input: ["string" => "(())"] | []
   ├─ Операции:
   │   ├─ isset($requestData['string']) → boolean
   │   ├─ is_string($requestData['string']) → boolean
   │   └─ Проверка обязательности поля
   ├─ Output: raw string value | ValidationError
   └─ Ошибки: "Поле \"string\" обязательно и должно быть строкой"

🔄 **НОРМАЛИЗАЦИЯ ВХОДНЫХ ДАННЫХ** (ValidationController):
   ├─ Input: "  (())  " (raw string with whitespace)
   ├─ Операции:
   │   ├─ trim($requestData['string']) → clean string
   │   └─ empty($inputString) → validation check
   ├─ Output: "(())" (normalized string)
   └─ Ошибки: InvalidArgumentException → "Empty input."

🔄 **ПОДГОТОВКА К БИЗНЕС-ВАЛИДАЦИИ** (BracketValidationService::processBrackets):
   ├─ Input: "(())" (single string)
   ├─ Операции:
   │   ├─ Обертывание в массив: [$inputString]
   │   ├─ foreach iteration: $bracketString
   │   ├─ is_string($bracketString) ? trim($bracketString) : ''
   │   └─ empty($cleanBrackets) → early validation
   ├─ Output: ValidationResult array | early InvalidFormat result
   └─ Предварительные проверки: пустая строка → ValidationResult::invalidFormat()

🔄 **АЛГОРИТМ ВАЛИДАЦИИ СКОБОК** (BracketValidator::validateBrackets):
   ├─ Input: "(())" (cleaned string)
   ├─ Операции:
   │   ├─ empty($brackets) → InvalidArgumentException check
   │   ├─ $balance = 0, $length = strlen($brackets)
   │   ├─ Character iteration: for ($i = 0; $i < $length; $i++)
   │   │   ├─ '(' → $balance++ (открывающая скобка)
   │   │   ├─ ')' → $balance--, if ($balance < 0) → invalid balance
   │   │   └─ other character → invalid format
   │   └─ Final check: $balance === 0 ? valid : invalid balance
   ├─ Output: ValidationResult object
   └─ Возможные результаты:
       ├─ ValidationResult::valid($brackets) → status: "valid"
       ├─ ValidationResult::invalidFormat($brackets) → status: "invalid_format"
       └─ ValidationResult::invalidBalance($brackets) → status: "invalid_balance"

🔄 **АГРЕГАЦИЯ РЕЗУЛЬТАТОВ** (BracketValidationService):
   ├─ Input: ValidationResult object
   ├─ Операции:
   │   ├─ Извлечение первого результата: $results[0]
   │   ├─ $result->isValid() → boolean determination
   │   └─ Preparation for API response
   ├─ Output: Single ValidationResult for API
   └─ Логика: один вход = один результат

🔄 **ФОРМИРОВАНИЕ HTTP-ОТВЕТА** (ValidationController):
   ├─ Input: ValidationResult {brackets: "(())", status: "valid"}
   ├─ Операции:
   │   ├─ Success path: $result->isValid() === true
   │   │   └─ JsonResponse::success(['status' => 'valid'])
   │   └─ Failed path: $result->isValid() === false
   │       └─ JsonResponse::failed(['status' => 'invalid'])
   ├─ Output: JsonResponse object with HTTP status
   └─ Статус-коды:
       ├─ Success: HTTP 200 + success payload
       └─ Failed: HTTP 400 + failed payload

📤 **HTTP RESPONSE:**
   ├─ Success: HTTP 200 OK
   │   ├─ Content-Type: application/json; charset=utf-8
   │   └─ Body: {"status": "valid"}
   ├─ Business Validation Failed: HTTP 400 Bad Request
   │   ├─ Content-Type: application/json; charset=utf-8
   │   └─ Body: {"status": "invalid"}
   └─ Input Validation Error: HTTP 400 Bad Request
       ├─ Content-Type: application/json; charset=utf-8
       └─ Body: {"error": {"message": "Поле \"string\" обязательно и должно быть строкой"}}

```

---

## 🔹 Мониторинг статуса Redis Cluster

### **Поток получения статуса системы**

```markdown

📥 **HTTP REQUEST:**
   ├─ Method: GET /api/status
   ├─ Headers: Accept: application/json
   └─ Body: пустое

🔄 **ПОЛУЧЕНИЕ ДАННЫХ REDIS** (RedisHealthController::getStatus):
   ├─ Input: пустой (GET endpoint)
   ├─ Операции:
   │   ├─ $redisChecker->isConnected() → boolean
   │   ├─ $redisChecker->getClusterStatus() → array of node statuses
   │   ├─ $redisChecker->getRequiredQuorum() → integer
   │   └─ Exception handling для каждой операции
   ├─ Output: Redis raw data | Throwable
   └─ Промежуточные данные:
       ├─ $redisConnected: true | false
       ├─ $clusterStatus: ["node1" => "connected", "node2" => "disconnected", ...]
       ├─ $requiredQuorum: integer (минимум узлов для работы)

🔄 **АГРЕГАЦИЯ HEALTH СТАТУСА** (RedisHealthController):
   ├─ Input: Raw Redis connection data
   ├─ Операции:
   │   ├─ Подсчет connected nodes: array_filter($clusterStatus, fn($status) => $status === 'connected')
   │   ├─ $connectedCount = count($connectedNodes)
   │   ├─ $totalNodes = count($clusterStatus)
   │   ├─ Health determination: $connectedCount >= $requiredQuorum
   │   ├─ Timestamp generation: date('c')
   │   └─ Server hostname: gethostname()
   ├─ Output: Structured health data
   └─ Структура данных:
       ├─ status: "OK" | "error" 
       ├─ service: "email-validator"
       ├─ version: "1.0.0"
       ├─ timestamp: ISO 8601 format
       ├─ server: hostname string
       ├─ redis_cluster: "connected" | "disconnected"
       └─ redis_details: detailed cluster information

🔄 **ФОРМИРОВАНИЕ ДЕТАЛЬНОЙ ИНФОРМАЦИИ** (RedisHealthController):
   ├─ Input: Aggregated health data
   ├─ Операции:
   │   ├─ Success scenario:
   │   │   ├─ cluster_status: "healthy"
   │   │   ├─ connected_nodes: $connectedCount
   │   │   ├─ total_nodes: $totalNodes  
   │   │   ├─ quorum_required: $requiredQuorum
   │   │   ├─ quorum_met: boolean
   │   │   └─ nodes: detailed node status mapping
   │   └─ Error scenario:
   │       ├─ cluster_status: "error"
   │       └─ error: exception message string
   ├─ Output: Complete status payload
   └─ Conditional formatting based on Redis connectivity

🔄 **ФОРМИРОВАНИЕ API-ОТВЕТА** (RedisHealthController):
   ├─ Input: Complete status data | Exception
   ├─ Операции:
   │   ├─ Success: JsonResponse::status('OK', $completeStatusData)
   │   └─ Error: JsonResponse::status('error', $errorStatusData)
   ├─ Output: JsonResponse object
   └─ Особенность: ВСЕГДА HTTP 200 (health check pattern)

📤 **HTTP RESPONSE:**
   ├─ Healthy System: HTTP 200 OK
   │   ├─ Content-Type: application/json; charset=utf-8
   │   └─ Body: {
   │       "status": "OK",
   │       "service": "email-validator",
   │       "version": "1.0.0", 
   │       "timestamp": "2024-01-15T10:30:00+00:00",
   │       "server": "hostname",
   │       "redis_cluster": "connected",
   │       "redis_details": {
   │         "cluster_status": "healthy",
   │         "connected_nodes": 2,
   │         "total_nodes": 3,
   │         "quorum_required": 2,
   │         "quorum_met": true,
   │         "nodes": {"node1": "connected", "node2": "connected", "node3": "disconnected"}
   │       }
   │     }
   └─ Unhealthy System: HTTP 200 OK (НЕ 500!)
       ├─ Content-Type: application/json; charset=utf-8
       └─ Body: {
           "status": "error",
           "service": "email-validator",
           "timestamp": "2024-01-15T10:30:00+00:00",
           "redis_cluster": "disconnected",
           "redis_details": {
             "cluster_status": "error",
             "error": "Connection timeout to Redis cluster"
           }
         }

```

---

## 🔹 Frontend Data Flow (Vue.js приложение)

### **Поток валидации через UI**

```markdown
📥 **USER INPUT:**
   ├─ Source: HTML input field | generateRandomBracketString() button
   ├─ Format: string (например: "((()))", "", "invalid123")
   └─ Storage: manualString.value (Vue 3 ref)

🔄 **ПОДГОТОВКА HTTP-ЗАПРОСА** (App.vue::submit):
   ├─ Input: manualString.value (Vue reactive data)
   ├─ Операции:
   │   ├─ const stringToSend = manualString.value
   │   ├─ Request payload formation: {string: stringToSend}
   │   ├─ Headers setup: {'Content-Type': 'application/json'}
   │   └─ Axios config preparation
   ├─ Output: Axios request configuration
   └─ Request structure:
       ├─ URL: '/api/validate'
       ├─ Method: POST
       ├─ Headers: Content-Type application/json
       └─ Data: {"string": "((()))"}

🔄 **HTTP-ВЗАИМОДЕЙСТВИЕ** (axios library):
   ├─ Input: Request configuration object
   ├─ Операции:
   │   ├─ Network request execution: axios.post(url, data, config)
   │   ├─ Response awaiting: Promise resolution
   │   ├─ JSON parsing: automatic by axios
   │   └─ Error classification: response vs network errors
   ├─ Output: Axios response object | Axios error object
   └─ Возможные исходы:
       ├─ Success response: {data: {status: "valid"}, status: 200}
       ├─ API error response: {data: {status: "invalid"}, status: 400}
       └─ Network error: error object without response property

🔄 **ОБРАБОТКА ОТВЕТОВ** (App.vue::handleApiError + submit success):
   ├─ Input: Axios response | Axios error
   ├─ Операции Success:
   │   └─ result.value = 'Корректная строка! Status: 200 OK.'
   ├─ Операции Error Processing:
   │   ├─ Network error: !error.response → 'Ошибка сети или сервер недоступен'
   │   ├─ Empty input: status === 400 && message.includes('Empty input')
   │   │   └─ 'Пустая строка! Status: 400 Bad Request.'
   │   ├─ Validation failed: status === 400 (other cases)
   │   │   └─ 'Некорректная строка! Status: 400 Bad Request.'
   │   └─ Server error: other status codes
   │       └─ 'Ошибка сервера: {status}'
   ├─ Output: User-friendly message string
   └─ Storage: result.value (Vue reactive ref)

🔄 **UI ОТОБРАЖЕНИЕ И СТИЛИЗАЦИЯ** (App.vue template):
   ├─ Input: result.value (reactive string)
   ├─ Операции:
   │   ├─ CSS class computation: answerClass = computed(() => {...})
   │   ├─ Message classification:
   │   │   ├─ startsWith('Корректная строка') → 'correct' (green)
   │   │   ├─ startsWith('Некорректная|Пустая строка') → 'incorrect' (red)
   │   │   └─ other messages → 'neutral' (default color)
   │   └─ DOM rendering with dynamic CSS classes
   ├─ Output: Styled HTML elements
   └─ Visual feedback: Color-coded result text

📤 **USER FEEDBACK:**
   ├─ Success Visual: "Корректная строка! Status: 200 OK." (зеленый текст)
   ├─ Invalid Visual: "Некорректная строка! Status: 400 Bad Request." (красный текст)
   ├─ Empty Input Visual: "Пустая строка! Status: 400 Bad Request." (красный текст)
   └─ Network Error Visual: "Ошибка сети или сервер недоступен" (нейтральный цвет)

```

### **Поток мониторинга Redis статуса**

```markdown
📥 **АВТОМАТИЧЕСКИЙ ЗАПРОС СТАТУСА:**
   ├─ Trigger: Vue lifecycle hooks (onMounted) + setInterval
   ├─ Timing: Initial delay 2s, then every 30s
   └─ Request: GET /api/status

🔄 **ОБРАБОТКА СТАТУСА REDIS** (App.vue::fetchRedisStatus):
   ├─ Input: пустой (GET request)
   ├─ Операции:
   │   ├─ HTTP request: axios.get('/api/status')
   │   ├─ Success: response.data.redis_cluster extraction
   │   └─ Error classification:
   │       ├─ Network errors: error.code === 'NETWORK_ERROR' || !error.response
   │       ├─ Server errors: status >= 500 → 'server_error'
   │       ├─ Not found: status === 404 → 'api_not_found'
   │       ├─ Client errors: status >= 400 → 'client_error'
   │       └─ Unknown: other cases → 'unknown_error'
   ├─ Output: Redis status string | error classification
   └─ Storage: redisStatus.value + isRedisStatusLoading.value

🔄 **UI ИНДИКАЦИЯ СТАТУСА** (App.vue computed properties):
   ├─ Input: redisStatus.value (reactive string)
   ├─ Операции:
   │   ├─ CSS class mapping: redisStatusClass computed property
   │   │   ├─ 'connected' → 'correct' (green)
   │   │   ├─ 'disconnected' → 'incorrect' (red)
   │   │   ├─ 'Loading...' → 'loading' (orange with pulse animation)
   │   │   ├─ 'network_error' → 'network-error' (orange)
   │   │   ├─ 'server_error' → 'server-error' (red)
   │   │   └─ other error types → specific error classes
   │   └─ Text mapping: redisStatusText computed property
   │       ├─ Technical statuses → User-friendly labels
   │       └─ Error codes → Readable error descriptions
   ├─ Output: CSS classes + display text
   └─ Rendering: Real-time status indicator

📤 **REDIS STATUS FEEDBACK:**
   ├─ Connected: "Redis Cluster: Connected" (зеленый)
   ├─ Disconnected: "Redis Cluster: Disconnected" (красный)
   ├─ Loading: "Redis Cluster: Loading..." (оранжевый с анимацией)
   ├─ Network Error: "Redis Cluster: Network Error" (оранжевый)
   ├─ Server Error: "Redis Cluster: Server Error" (красный)
   └─ Other Errors: "Redis Cluster: {Error Type}" (соответствующий цвет)
```

---

## 🔹 Централизованная обработка системных ошибок

### **Поток обработки исключений (Fallback)**

```markdown
📥 **СИСТЕМНЫЕ ИСКЛЮЧЕНИЯ:**
   ├─ Sources: Router, Controllers, Services, Validators
   ├─ Types: JsonException, InvalidArgumentException, RuntimeException, Throwable
   └─ Context: Любые непредвиденные ошибки системы

🔄 **ЦЕНТРАЛИЗОВАННЫЙ HANDLER** (App::handleException + Router fallbacks):
   ├─ Input: Throwable object с контекстом
   ├─ Операции:
   │   ├─ Exception type classification
   │   ├─ Error message sanitization (не раскрывать внутренние детали)
   │   ├─ HTTP status code determination:
   │   │   ├─ JsonException → 400 Bad Request
   │   │   ├─ InvalidArgumentException → 400 Bad Request  
   │   │   ├─ RuntimeException с кодом → использовать код (например 404)
   │   │   └─ Throwable (прочие) → 500 Internal Server Error
   │   └─ Structured error response formation
   ├─ Output: JsonResponse с error payload
   └─ Error format standardization

📤 **СИСТЕМНЫЕ ERROR RESPONSES:**
   ├─ JSON Parse Error: HTTP 400 Bad Request
   │   └─ Body: {"error": {"message": "Некорректный JSON в запросе: ..."}}
   ├─ Validation Error: HTTP 400 Bad Request
   │   └─ Body: {"error": {"message": "Поле \"string\" обязательно и должно быть строкой"}}
   ├─ Route Not Found: HTTP 404 Not Found
   │   └─ Body: {"error": {"message": "Маршрут не найден"}}
   ├─ Invalid URI: HTTP 400 Bad Request
   │   └─ Body: {"error": {"message": "Недопустимый URI"}}
   └─ Internal Error: HTTP 500 Internal Server Error
       └─ Body: {"error": {"message": "Внутренняя ошибка сервера", "code": "INTERNAL_ERROR"}}

```

---

## 📋 Ключевые принципы Data Flow Architecture проекта:

### **Типы данных:**
- **HTTP Request/Response**: JSON-структуры с типизированными полями
- **Internal Objects**: ValidationResult, JsonResponse с определенными свойствами  
- **Primitive Data**: strings, booleans, integers с четкими ограничениями
- **Error Data**: структурированные объекты исключений с контекстом

### **Трансформации:**
- **Parsing**: binary → JSON → PHP arrays
- **Validation**: raw input → normalized data → validated objects
- **Business Logic**: input data → domain objects → result objects
- **Serialization**: PHP objects → JSON → HTTP responses

### **Обработка ошибок:**
- **Ранняя валидация**: на уровне входных данных
- **Бизнес-валидация**: на уровне доменной логики
- **Централизованная обработка**: единообразные error responses
- **Graceful degradation**: fallback responses для системных ошибок

### **Консистентность форматов:**
- Все API responses используют единый JsonResponse format
- HTTP статусы соответствуют семантике ошибок
- Error messages структурированы и локализованы
- Success/failed responses имеют предсказуемую структуру
