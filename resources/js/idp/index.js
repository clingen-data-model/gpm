import { ref } from 'vue'
import { getIdpConfig, idpDriver, idpEnabled } from './config'
import { useFakeIdp } from './fake_idp'

/**
 * Uniform view of the external identity provider for the SPA:
 *   { driver, isLoaded, isSignedIn, getToken(), signOut() }
 * Backed by @clerk/vue, the offline fake, or a null implementation.
 * Must be called from setup() (Clerk's composables need component context).
 */
export function useIdp() {
    const driver = idpDriver()

    if (driver === 'clerk') {
        // Resolved lazily so the fake/null drivers never load Clerk code.
        const { useClerkIdp } = clerkModule()
        return useClerkIdp()
    }

    if (driver === 'fake') {
        return useFakeIdp()
    }

    return {
        driver: 'null',
        isLoaded: ref(true),
        isSignedIn: ref(false),
        getToken: async () => null,
        signOut: async () => undefined,
    }
}

let clerkIdpModule = null

export function registerClerkIdpModule(module) {
    clerkIdpModule = module
}

function clerkModule() {
    if (!clerkIdpModule) {
        throw new Error('Clerk IdP module was not registered; app.js installs it when the driver is clerk.')
    }
    return clerkIdpModule
}

export { getIdpConfig, idpDriver, idpEnabled }
