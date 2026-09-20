import { computed, ref } from 'vue'

/**
 * Fake identity provider (IDP_DRIVER=fake): the token minted by
 * POST /dev/idp/token is kept in sessionStorage so a reload behaves like a
 * still-signed-in Clerk session would.
 */
const STORAGE_KEY = 'gpm.fakeIdpToken'

function readStoredToken() {
    try {
        return window.sessionStorage.getItem(STORAGE_KEY)
    } catch {
        return null
    }
}

const token = ref(readStoredToken())
const isLoaded = ref(true)
const isSignedIn = computed(() => Boolean(token.value))

export function setFakeIdpToken(value) {
    token.value = value || null
    try {
        if (value) {
            window.sessionStorage.setItem(STORAGE_KEY, value)
        } else {
            window.sessionStorage.removeItem(STORAGE_KEY)
        }
    } catch {
        // storage unavailable (private mode, tests): keep it in memory only
    }
}

export function useFakeIdp() {
    return {
        driver: 'fake',
        isLoaded,
        isSignedIn,
        getToken: async () => token.value,
        signOut: async () => setFakeIdpToken(null),
    }
}
