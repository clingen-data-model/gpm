import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import MockAdapter from 'axios-mock-adapter'
import api from '@/http/api'
import store from '@/store'

describe('store idpSessionLogin', () => {
    let mock

    beforeEach(() => {
        mock = new MockAdapter(api)
        store.commit('clearCurrentUser')
    })

    afterEach(() => mock.restore())

    it('fetches the csrf cookie, posts the bearer token, then loads the current user', async () => {
        const calls = []
        mock.onGet('/sanctum/csrf-cookie').reply(() => { calls.push('csrf'); return [204] })
        mock.onPost('/api/idp/session-login').reply((config) => {
            calls.push('exchange')
            expect(config.headers.Authorization).toBe('Bearer tok.en')
            return [200, { user_id: 7 }]
        })
        mock.onGet('/api/current-user').reply(200, { data: { id: 7, name: 'Jane', email: 'jane@example.com', roles: [], permissions: [], person: { memberships: [] } } })
        mock.onGet('/api/system-info').reply(200, { build: {}, app: { features: {} } })

        await store.dispatch('idpSessionLogin', 'tok.en')

        expect(calls).toEqual(['csrf', 'exchange'])
        expect(store.getters.isAuthed).toBe(true)
        expect(store.getters.currentUser.id).toBe(7)
    })

    it('rejects when the exchange is refused and stays signed out', async () => {
        mock.onGet('/sanctum/csrf-cookie').reply(204)
        mock.onPost('/api/idp/session-login').reply(403, { message: 'This account is not linked to a GPM user.' })

        await expect(store.dispatch('idpSessionLogin', 'tok.en')).rejects.toMatchObject({ response: { status: 403 } })

        expect(store.getters.isAuthed).toBe(false)
        expect(store.getters.currentUser.id).toBeNull()
    })
})
