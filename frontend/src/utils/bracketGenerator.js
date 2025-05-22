export function generateValidBracketString(maxLength = 30) {
    const maxPairs = Math.floor(maxLength / 2)
    let result = ''
    let open = 0

    for (let i = 0; i < maxPairs; i++) {
        result += '('
        open++
        if (Math.random() > 0.5 && open > 0) {
            result += ')'
            open--
        }
    }

    result += ')'.repeat(open)
    return result
}

export function generateInvalidBracketString(maxLength = 30) {
    const length = Math.floor(Math.random() * maxLength) + 1
    const chars = ['(', ')']
    let str = ''
    for (let i = 0; i < length; i++) {
        str += chars[Math.floor(Math.random() * 2)]
    }

    if (!str.includes('(') || !str.includes(')')) {
        str += '('
    }

    return str
}

export function generateRandomBracketString(maxLength = 30) {
    const shouldBeValid = Math.random() < 0.5
    return shouldBeValid
        ? generateValidBracketString(maxLength)
        : generateInvalidBracketString(maxLength)
}
