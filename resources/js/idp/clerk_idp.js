import { useAuth } from '@clerk/vue'

/**
 * Clerk-backed identity provider. Requires clerkPlugin to be installed
 * (see app.js); @clerk/vue exposes every value as a computed ref, hence the
 * `.value(...)` calls for the functions.
 */
export function useClerkIdp() {
    const { isLoaded, isSignedIn, getToken, signOut } = useAuth()

    return {
        driver: 'clerk',
        isLoaded,
        isSignedIn,
        getToken: async () => (getToken.value ? getToken.value() : null),
        signOut: async () => (signOut.value ? signOut.value() : undefined),
    }
}
