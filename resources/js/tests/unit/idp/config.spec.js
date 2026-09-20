import { afterEach, describe, expect, it } from 'vitest'
import { getIdpConfig, idpEnabled } from '@/idp/config'

describe('idp config', () => {
    afterEach(() => {
        delete window.__GPM_IDP__
    })

    it('defaults to the null driver when nothing is injected', () => {
        expect(getIdpConfig().driver).toBe('null')
        expect(idpEnabled()).toBe(false)
    })

    it('reads the values injected by the server', () => {
        window.__GPM_IDP__ = { driver: 'clerk', clerk: { publishableKey: 'pk_test_x' } }

        expect(getIdpConfig()).toEqual({ driver: 'clerk', clerk: { publishableKey: 'pk_test_x' } })
        expect(idpEnabled()).toBe(true)
    })
})
