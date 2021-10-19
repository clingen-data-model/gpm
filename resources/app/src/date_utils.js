const formatDate = function(date) {
    if (date === null || typeof date == 'undefined') {
        return null;
    }
    const d = new Date(date);
    // const year = d.getFullYear().toString();
    // const month = (d.getMonth() + 1).toString().padStart(2, '0');
    // const day = (d.getDate()).toString().padStart(2, '0');

    return d.toLocaleDateString();
}

const formatDateTime = function(date) {
    const d = new Date(date);
    // const year = d.getFullYear().toString();
    // const month = (d.getMonth() + 1).toString().padStart(2, '0');
    // const day = (d.getDate()).toString().padStart(2, '0');
    // const time = (d.getDate()).toString().padStart(2, '0');

    return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], {timeStyle: 'short'})}`;
}

const yearAgo = () => {
    const d = new Date();
    return d.setFullYear(d.getFullYear()-1);
}

export { formatDate, formatDateTime, yearAgo }