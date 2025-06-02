<template>
  <div class="container">
    <h1>Проверка последовательности скобок</h1>

    <!-- Поле ввода строки (вручную или сгенерированной) -->
    <div class="input-block">
      <div class="output-title">Строка (или сгенерированная):</div>
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
</template>

<script setup>
// Импортируем функции Vue
import {ref, computed} from 'vue'
// Импортируем axios для отправки HTTP-запросов
import axios from 'axios'
// Импортируем утилиту генерации скобочных строк
import {generateRandomBracketString} from './utils/bracketGenerator'

// Переменная для хранения введённой или сгенерированной строки
const manualString = ref('')
// Переменная для хранения текста результата
const result = ref('')

// Функция генерации случайной скобочной строки
const generate = () => {
  manualString.value = generateRandomBracketString() // генерируем строку
  result.value = '' // очищаем прошлый результат
}

// Функция отправки строки на сервер для проверки
const submit = async () => {
  const stringToSend = manualString.value

  try {
    // Отправляем POST-запрос на /api/ с параметром string
    const response = await axios.post('/api/', new URLSearchParams({string: stringToSend}))
    result.value = 'Корректная строка! Status: 200 OK.' // если успешно
  } catch (error) {
    if (error.response) {
      if (error.response.status === 400) {
        const errorMessage = error.response.data.error || ''
        // Если ошибка о пустом вводе
        if (errorMessage.includes('Empty input')) {
          result.value = 'Пустая строка! Status: 400 Bad Request.'
        } else {
          result.value = 'Некорректная строка! Status: 400 Bad Request.'
        }
      } else {
        result.value = 'Ошибка при проверке строки' // прочие ошибки
      }
    } else {
      result.value = 'Ошибка при проверке строки' // сетевая ошибка или сбой
    }
  }
}

// Вычисляем CSS-класс для текста ответа
const answerClass = computed(() => {
  if (result.value.startsWith('Корректная строка')) {
    return 'correct' // зелёный цвет
  } else if (result.value.startsWith('Некорректная строка') || result.value.startsWith('Пустая строка')) {
    return 'incorrect' // красный цвет
  } else {
    return 'neutral' // чёрный цвет
  }
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

/* Блок переключателей режима (сейчас не используется, но оставлен в стилях) */
.mode-select {
  margin-bottom: 1.5rem;
  text-align: center;
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
</style>
