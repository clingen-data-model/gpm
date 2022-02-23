import {ref} from 'vue'

export const pageSize = 20;
export const currentPage = ref(0);

export const getPageItems = (allItems) => {
    const startIndex = currentPage.value * pageSize;
    const endIndex = startIndex + pageSize - 1;
    return allItems.slice(startIndex, endIndex);
}

