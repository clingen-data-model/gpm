export const titleCase = string => {
    const parts = string.split(' ');
    return parts.map(str => str.charAt(0).toUpperCase()+str.slice(1)).join(' ');
}