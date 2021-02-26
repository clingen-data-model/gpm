const formatDate = function(date) {
    const d = new Date(date);
    const year = d.getFullYear().toString();
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = (d.getDate() + 1).toString().padStart(2, '0');

    return `${year}-${month}-${day}`
}

export { formatDate }