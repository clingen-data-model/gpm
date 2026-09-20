import { beforeEach, describe, expect, it } from 'vitest'
import { setFakeIdpToken, useFakeIdp } from '@/idp/fake_idp'

describe('fake idp', () => {
    beforeEach(() => setFakeIdpToken(null))

    it('starts signed out and exposes the token once set', async () => {
        const idp = useFakeIdp()
        expect(idp.driver).toBe('fake')
        expect(idp.isLoaded.value).toBe(true)
        expect(idp.isSignedIn.value).toBe(false)
        expect(await idp.getToken()).toBeNull()

        setFakeIdpToken('abc.def.ghi')

        expect(idp.isSignedIn.value).toBe(true)
        expect(await idp.getToken()).toBe('abc.def.ghi')
        expect(window.sessionStorage.getItem('gpm.fakeIdpToken')).toBe('abc.def.ghi')
    })

    it('signing out clears the token and storage', async () => {
        setFakeIdpToken('abc.def.ghi')
        const idp = useFakeIdp()

        await idp.signOut()

        expect(idp.isSignedIn.value).toBe(false)
        expect(window.sessionStorage.getItem('gpm.fakeIdpToken')).toBeNull()
    })
})
