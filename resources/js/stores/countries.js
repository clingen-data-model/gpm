import { createCrudStore } from './createCrudStore'

export const useCountriesStore = createCrudStore('countries', {
    baseUrl: '/api/countries',
})
