<template>
  <div class="container">
    <h1>Проверка скобочной строки</h1>

    <div class="section">
      <label>Введите строку вручную:</label>
      <textarea v-model="bracketString" rows="3" class="input-area" />
      <button @click="validateString">Проверить</button>
    </div>

    <hr />

    <div class="section">
      <label>Генерация строки:</label>
      <input type="number" v-model.number="maxLength" min="2" max="100" />
      <button @click="generateAndValidate">Сгенерировать и проверить</button>
    </div>

    <div class="result" v-if="result !== null">
      <p :class="{ success: isSuccess, error: !isSuccess }">{{ result }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import {
  generateRandomBracketString
} from './utils/bracketGenerator'

const bracketString = ref('')
const maxLength = ref(30)
const result = ref(null)
const isSuccess = ref(false)

const validateString = async () => {
  try {
    const res = await axios.post('/api/validate', {
      string: bracketString.value
    })

    isSuccess.value = true
    result.value = res.data?.message || '✅ Строка корректна'
  } catch (err) {
    isSuccess.value = false
    result.value =
        err.response?.data?.message || '❌ Строка некорректна'
  }
}

const generateAndValidate = async () => {
  bracketString.value = generateRandomBracketString(maxLength.value)
  await validateString()
}
</script>

<style scoped>
.container {
  max-width: 600px;
  margin: 40px auto;
  font-family: sans-serif;
}

.section {
  margin-bottom: 20px;
}

.input-area {
  width: 100%;
  font-family: monospace;
  font-size: 1rem;
}

button {
  margin-top: 8px;
  padding: 6px 12px;
  font-size: 1rem;
  cursor: pointer;
}

.result {
  margin-top: 20px;
}

.success {
  color: green;
  font-weight: bold;
}

.error {
  color: crimson;
  font-weight: bold;
}
</style>
