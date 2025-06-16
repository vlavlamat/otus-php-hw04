/**
 * @file bracketGenerator.js
 * @description Утилиты для генерации строк со скобками (правильных и неправильных)
 */


/**
 * Генерирует валидную (корректную) скобочную строку
 * @param {number} maxLength - Максимальная длина генерируемой строки
 * @returns {string} Валидная строка со скобками
 */
export function generateValidBracketString(maxLength = 20) {
    const pairs = Math.floor(Math.random() * (maxLength / 2)) + 1 // выбираем случайное количество пар скобок (минимум 1 пара)
    let result = '' // результирующая строка
    let open = 0 // количество открытых скобок, которые нужно закрыть

    for (let i = 0; i < pairs; i++) {
        result += '(' // добавляем открывающую скобку
        open++ // увеличиваем счётчик открытых скобок
        if (Math.random() > 0.5 && open > 0) { // с 50% вероятностью (и если есть что закрывать)
            result += ')' // добавляем закрывающую скобку
            open-- // уменьшаем счётчик открытых скобок
        }
    }

    result += ')'.repeat(open) // закрываем все оставшиеся открытые скобки
    return result // возвращаем готовую строку
}

/**
 * Генерирует невалидную (некорректную) скобочную строку
 * @param {number} maxLength - Максимальная длина генерируемой строки
 * @returns {string} Невалидная строка со скобками
 */
export function generateInvalidBracketString(maxLength = 20) {
    const length = Math.floor(Math.random() * (maxLength - 1)) + 2 // случайная длина строки (минимум 2 символа)
    const chars = ['(', ')'] // доступные символы
    let str = ''
    for (let i = 0; i < length; i++) {
        str += chars[Math.floor(Math.random() * 2)] // случайным образом выбираем '(' или ')'
    }

    // на всякий случай добавляем хотя бы одну открывающую и одну закрывающую, если их нет
    if (!str.includes('(')) {
        str += '('
    }
    if (!str.includes(')')) {
        str += ')'
    }

    return str // возвращаем хаотичную строку
}

/**
 * Генерирует случайную строку со скобками (валидную или невалидную)
 * то есть случайно выбирает какую строку надо генерировать
 * @returns {string} Случайная строка со скобками
 */
export function generateRandomBracketString() {
    const shouldBeValid = Math.random() < 0.5 // с 50% вероятностью выбираем тип
    return shouldBeValid
        ? generateValidBracketString(20) // если да — генерируем валидную
        : generateInvalidBracketString(20) // если нет — генерируем невалидную
}
