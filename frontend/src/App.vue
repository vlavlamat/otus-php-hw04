<template>
  <div class="container">
    <h1>Проверка последовательности скобок</h1>

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
      <!-- Отображаем статус Redis Cluster (connected/disconnected) -->
      Redis Cluster: <span :class="redisStatusClass">{{ redisStatus }}</span>
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
    redisStatus.value = response.data.redis_cluster  // Получаем поле redis_cluster
  } catch (error) {
    redisStatus.value = 'disconnected'
    console.error('Ошибка при получении статуса Redis:', error)
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
 * Обрабатывает ошибку API
 * @param {Error} error - Объект ошибки от axios
 * @returns {string} Сообщение об ошибке для отображения пользователю
 */
const handleApiError = (error) => {
  if (!error.response) {
    return 'Ошибка сети или сервер недоступен'
  }

  const {status, data} = error.response
  if (status === 400) {
    const errorMessage = data.message || ''
    return errorMessage.includes('Empty input')
        ? 'Пустая строка! Status: 400 Bad Request.'
        : 'Некорректная строка! Status: 400 Bad Request.'
  }

  return `Ошибка сервера: ${status}`
}

/**
 * Отправляет строку на сервер для проверки
 * @async
 */
const submit = async () => {
  const stringToSend = manualString.value

  try {
    await axios.post('/api/validate', {
      string: stringToSend
    }, {
      headers: {
        'Content-Type': 'application/json'
      }
    })
    result.value = 'Корректная строка! Status: 200 OK.'
  } catch (error) {
    result.value = handleApiError(error)
  }
}

/**
 * Вычисляемые свойства
 */

/**
 * Определяет CSS-класс для текста ответа в зависимости от результата
 * @returns {string} CSS-класс (correct, incorrect или neutral)
 */
const answerClass = computed(() => {
  if (result.value.startsWith('Корректная строка')) {
    return 'correct'
  } else if (result.value.startsWith('Некорректная строка') || result.value.startsWith('Пустая строка')) {
    return 'incorrect'
  } else {
    return 'neutral'
  }
})

/**
 * Определяет CSS-класс для отображения статуса Redis Cluster
 * @returns {string} CSS-класс (loading, correct или incorrect)
 */
const redisStatusClass = computed(() => {
  if (redisStatus.value === 'Loading...') {
    return 'loading'
  }
  // Если статус 'connected', применяем класс 'correct' (зеленый цвет),
  // иначе применяем класс 'incorrect' (красный цвет)
  return redisStatus.value === 'connected' ? 'correct' : 'incorrect'
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

.mode-select label {
  margin: 0 1rem; /* отступы между label */
  font-size: 1.2rem;
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