<template>
  <div class="container">
    <h1>Сервис валидации скобок</h1>

    <!-- Поле ввода строки (вручную или сгенерированной) -->
    <div class="input-block">
      <div class="output-title">Строка:</div>
      <input
          type="text"
          v-model="manualString"
          class="input-field"
          placeholder="Введите строку или сгенерируйте"
      />

      <!-- Кнопка генерации случайной строки -->
      <button
          class="action-button"
          @click="generate"
      >
        Сгенерировать
      </button>
    </div>

    <!-- Кнопка отправки строки на проверку -->
    <button class="submit-button" @click="submit">Проверить</button>

    <!-- Блок вывода результата -->
    <div class="output">
      <div class="output-section">
        <div class="output-title">Ответ:</div>
        <div class="answer-container">
          <!-- Выводим текст ответа с динамическим классом для окрашивания -->
          <div class="answer-text" :class="answerClass">{{ result }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Индикатор статуса Redis Cluster в отдельном контейнере -->
  <div class="redis-status-container">
    <div class="redis-status">
      <!-- Отображаем статус Redis Cluster с детальной обработкой ошибок -->
      Redis Cluster: <span :class="redisStatusClass">{{ redisStatusText }}</span>
    </div>
  </div>
</template>

<script setup>
/**
 * @file App.vue
 * @description Компонент приложения для проверки корректности последовательности скобок
 */

// Импортируем функции Vue
import {ref, computed, onMounted, onUnmounted} from 'vue'
// Импортируем axios для отправки HTTP-запросов
import axios from 'axios'
// Импортируем утилиту генерации скобочных строк
import {generateRandomBracketString} from './utils/bracketGenerator'

/**
 * Константы для настройки интервалов проверки статуса Redis
 */
const REDIS_STATUS_CHECK_DELAY = 2000
const REDIS_STATUS_CHECK_INTERVAL = 30000

/**
 * Состояние приложения
 */
// Переменная для хранения введённой или сгенерированной строки
const manualString = ref('')
// Переменная для хранения текста результата
const result = ref('')
// Переменная для хранения статуса Redis Cluster
const redisStatus = ref('Loading...')
// Флаг для отслеживания загрузки статуса Redis
const isRedisStatusLoading = ref(true)
// Переменная для хранения идентификатора интервала
let statusInterval = null

/**
 * Получает статус Redis Cluster с сервера
 * @async
 * @returns {Promise<void>} Промис без возвращаемого значения
 */
const fetchRedisStatus = async () => {
  try {
    const response = await axios.get('/api/status') // Запрос к backend
    const {redis_cluster} = response.data
    redisStatus.value = redis_cluster  // Получаем 'connected' или 'disconnected'
  } catch (error) {
    // Единственная возможная ошибка - недоступность backend через nginx
    redisStatus.value = 'backend_unavailable'

    // Логируем для разработчика
    console.error('Backend unavailable:', {
      message: error.message,
      status: error.response?.status || 'No response'
    })
  } finally {
    // Устанавливаем флаг загрузки в false после получения статуса
    isRedisStatusLoading.value = false
  }
}

/**
 * Обработчики жизненного цикла компонента
 */
onMounted(() => {
  setTimeout(fetchRedisStatus, REDIS_STATUS_CHECK_DELAY)
  statusInterval = setInterval(fetchRedisStatus, REDIS_STATUS_CHECK_INTERVAL)
})

onUnmounted(() => {
  if (statusInterval) {
    clearInterval(statusInterval)
  }
})

/**
 * Генерирует случайную скобочную строку
 */
const generate = () => {
  manualString.value = generateRandomBracketString()
  result.value = ''
}

/**
 * Отправляет строку на сервер для проверки
 * @async
 */

const submit = async () => {
  try {
    const {data, status} = await axios.post('/api/validate', {string: manualString.value});

    // 200 OK - только valid
    if (status === 200 && data?.status === 'valid') {
      result.value = 'Корректная строка! Status: 200 OK.';
      return;
    }

    // Нестандартный положительный ответ
    result.value = 'Неожиданный ответ сервера.';
  } catch (err) {
    // Сетевая ошибка или сервер не ответил
    if (!err.response) {
      result.value = 'Ошибка сети или сервер недоступен';
      return;
    }

    const {status, data} = err.response;

    // 1) Бизнес-ошибки валидации: ожидаем поле data.status
    if (data && typeof data === 'object' && 'status' in data) {
      switch (data.status) {
        case 'invalid':
          result.value = 'Некорректная строка! Status: 400 Bad Request.';
          return;
        case 'empty':
          result.value = 'Пустая строка! Status: 400 Bad Request.';
          return;
        case 'invalid_format':
          result.value = 'Недопустимые символы! Status: 400 Bad Request.';
          return;
      }
    }

    // 2) Технические ошибки централизованного обработчика: {error: {message}}
    const message = data?.error?.message || 'Неизвестная ошибка';

    if (status === 400) {
      // сюда попадает, например, "Некорректный JSON в запросе"
      result.value = `Ошибка запроса: ${message}. Status: 400 Bad Request.`;
      return;
    }

    if (status >= 500) {
      result.value = `Ошибка сервера: ${message}. Status: ${status}.`;
      return;
    }

    // Прочие случаи
    result.value = `Ошибка: ${message}. Status: ${status}.`;
  }
};

/**
 * Вычисляемые свойства
 */

/**
 * Определяет CSS-класс для текста ответа в зависимости от результата
 * @returns {string} CSS-класс (correct, incorrect или neutral)
 */
const answerClass = computed(() => {
  // Приведение к строке для IDE
  const resultText = String(result.value)

  if (resultText.startsWith('Корректная строка')) {
    return 'correct'
  } else if (resultText.startsWith('Некорректная строка') || resultText.startsWith('Пустая строка')) {
    return 'incorrect'
  } else {
    return 'neutral'
  }
})

/**
 * Определяет CSS-класс для отображения статуса Redis Cluster
 * @returns {string} CSS-класс для разных типов ошибок
 */
const redisStatusClass = computed(() => {
  const statusMap = {
    'Loading...': 'loading',
    'connected': 'correct',
    'disconnected': 'incorrect',
    'backend_unavailable': 'backend-error'
  }

  return statusMap[redisStatus.value] || 'incorrect'
})

/**
 * Определяет отображаемый текст для статуса Redis Cluster
 * @returns {string} Пользовательский текст статуса
 */
const redisStatusText = computed(() => {
  const textMap = {
    'Loading...': 'Loading...',
    'connected': 'Connected',
    'disconnected': 'Disconnected',
    'backend_unavailable': 'Backend Unavailable'
  }

  return textMap[redisStatus.value] || 'Error'
})
</script>

<style scoped>
/* Основной контейнер приложения */
.container {
  max-width: 600px; /* ограничиваем ширину */
  margin: 3rem auto; /* отступ сверху/снизу и автоцентрирование */
  padding: 2rem; /* внутренние отступы */
  font-family: sans-serif; /* шрифт */
  background-color: #f9f9f9; /* светлый фон */
  border-radius: 12px; /* скруглённые углы */
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* тень вокруг блока */
}

/* Заголовок */
h1 {
  font-size: 2rem; /* размер шрифта */
  margin-bottom: 1.5rem; /* отступ снизу */
  text-align: center; /* центрирование текста */
}

/* Блок с полем ввода */
.input-block {
  margin-bottom: 1rem;
}

/* Заголовок внутри блоков */
.output-title {
  font-size: 1.3rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  text-align: left;
}

/* Поле ввода текста */
.input-field {
  width: 100%;
  padding: 0.8rem;
  font-size: 1.1rem;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-sizing: border-box;
  margin-bottom: 0.5rem;
}

/* Стили для заблокированного поля */
.input-field:disabled {
  background-color: #f0f0f0;
  cursor: not-allowed;
}

/* Общие стили кнопок */
button {
  padding: 0.8rem;
  font-size: 1.1rem;
  border: none;
  border-radius: 8px;
  background-color: #007bff; /* синий цвет */
  color: white;
  cursor: pointer;
  transition: background-color 0.3s ease; /* плавное изменение цвета */
}

/* Стили для заблокированных кнопок */
button:disabled {
  background-color: #aaa;
  cursor: not-allowed;
}

/* Стили кнопок при наведении */
button:hover:enabled {
  background-color: #0056b3;
}

/* Стили для кнопок "Сгенерировать" и "Отправить" */
.action-button,
.submit-button {
  width: 100%;
  margin-top: 0.5rem;
}

/* Блок с результатом */
.output {
  margin-top: 2rem;
}

/* Внутренний блок с секцией результата */
.output-section {
  margin-bottom: 1.5rem;
}

/* Контейнер для текста ответа */
.answer-container {
  width: 100%;
  min-height: 4rem; /* минимальная высота */
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.8rem;
  background-color: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  box-sizing: border-box;
}

/* Текст ответа */
.answer-text {
  font-size: 1.4rem;
  font-weight: bold;
  text-align: center;
}

/* Цвет текста для корректной строки */
.answer-text.correct {
  color: green;
}

/* Цвет текста для некорректной строки */
.answer-text.incorrect {
  color: red;
}

/* Цвет текста для нейтрального состояния (по умолчанию) */
.answer-text.neutral {
  color: black;
}

/* Контейнер для индикатора статуса Redis Cluster */
.redis-status-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 0.5rem;
  display: flex;
  justify-content: center;
}

/* Стили для индикатора статуса Redis Cluster */
.redis-status {
  text-align: center;
  font-size: 14px;
  font-weight: bold;
}

/* Стили для статуса Redis Cluster */
.redis-status span.correct {
  color: green;
}

.redis-status span.loading {
  color: #f0ad4e; /* желтый/оранжевый цвет для состояния загрузки */
  animation: pulse 1.5s infinite; /* добавляем пульсирующую анимацию */
}

.redis-status span.incorrect {
  color: red;
}

.redis-status span.backend-error {
  color: #ff6b35; /* ярко-оранжевый для недоступности backend */
  animation: pulse 2s infinite; /* пульсирующая анимация для привлечения внимания */
}

@keyframes pulse {
  0% {
    opacity: 0.6;
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0.6;
  }
}
</style>
