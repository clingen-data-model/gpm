import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import MockAdapter from 'axios-mock-adapter'
import api from '@/http/api'
import store from '@/store'

describe('store response interceptor', () => {
    let mock

    beforeEach(() => {
        mock = new MockAdapter(api)
        store.commit('setAuthenticated', true)
        store.state.alerts.alerts = []
    })

    afterEach(() => mock.restore())

    const errorMessages = () => store.state.alerts.alerts.map(a => a.message)

    it('treats a network failure as an error alert instead of crashing', async () => {
        mock.onGet('/api/anything').networkError()

        await expect(api.get('/api/anything')).rejects.toBeTruthy()

        expect(errorMessages().some(m => m.includes('could not reach the server'))).toBe(true)
        expect(store.getters.isAuthed).toBe(true)
    })

    it('marks the session as gone on 419', async () => {
        mock.onPost('/api/something').reply(419, { message: 'CSRF token mismatch.' })

        await expect(api.post('/api/something')).rejects.toBeTruthy()

        expect(store.getters.isAuthed).toBe(false)
        expect(errorMessages().some(m => m.includes('session has expired'))).toBe(true)
    })

    it('marks the session as gone on 401 without an alert', async () => {
        mock.onGet('/api/something').reply(401)

        await expect(api.get('/api/something')).rejects.toBeTruthy()

        expect(store.getters.isAuthed).toBe(false)
        expect(errorMessages()).toEqual([])
    })

    it('lets callers opt out of the global alerts', async () => {
        mock.onGet('/api/quiet').reply(403, { message: 'nope' })

        await expect(api.get('/api/quiet', { skipErrorAlert: true })).rejects.toBeTruthy()

        expect(errorMessages()).toEqual([])
    })
})
