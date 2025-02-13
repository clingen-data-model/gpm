/**
 * Normalizes the case of a string to lower, space, cased
 * @param {string} string
 */
 export const normalizeCase = string => {
    if (!string) {
        return string
    }
    const val = string.replace(/[A-Z]/g, letter => ` ${letter.toLowerCase()}`)
            .split(/[\s_-]/)
            .filter(w => w !== ' ' && w !== '')
            .map(w => w.trim())
            .join(' ')
            .trim();
    return val;
}

export const titleCase = string => {
    if (!string) {
        return string
    }
    const parts = normalizeCase(string).split(' ');
    return parts.map(str => str.charAt(0).toUpperCase()+str.slice(1)).join(' ');
}

export const kebabCase = string => {
    if (!string) {
        return string
    }
    return normalizeCase(string).split(' ').join('-');
}

export const snakeCase = string => {
    if (!string) {
        return string
    }
    return normalizeCase(string).split(' ').join('_');
}

export const studlyCase = string => {
    if (!string) {
        return string
    }
    const parts = normalizeCase(string).split(' ');
    return parts.map(str => str.charAt(0).toUpperCase()+str.slice(1)).join('');
}

export const camelCase = string => {
    const studly = studlyCase(string);

    return studly.charAt(0).toLowerCase()+studly.slice(1)
}

export const sentenceCase = str => {
    if (str === null) {
        return str
    }
    const parts = normalizeCase(str).split(' ');
    return parts.join(' ').charAt(0).toUpperCase()+parts.join(' ').slice(1);
}

export default {
    normalizeCase,
    titleCase,
    kebabCase,
    camelCase,
    snakeCase,
}
