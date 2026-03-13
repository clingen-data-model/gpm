import { createCrudStore } from './createCrudStore'

export const useDoctypesStore = createCrudStore('doctypes', {
    baseUrl: '/api/document-types',
})
