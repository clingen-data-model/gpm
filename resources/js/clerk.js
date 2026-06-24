import { Clerk } from '@clerk/clerk-js'

/**
 * Single Clerk client for the SPA.
 *
 * Clerk owns authentication (sign-in/up, password reset, MFA) via its prebuilt
 * components. The rest of the app (axios interceptor, Vuex store, router guard)
 * reads identity/session state from this shared instance. Authorization still
 * comes from our own backend (`/api/current-user`).
 */
const publishableKey = import.meta.env.VITE_CLERK_PUBLISHABLE_KEY

if (!publishableKey) {
    // eslint-disable-next-line no-console
    console.error('VITE_CLERK_PUBLISHABLE_KEY is not set; authentication will not work.')
}

export const clerk = new Clerk(publishableKey)

// A single load promise shared everywhere so we never call load() twice and
// callers can await readiness before touching `clerk.user` / `clerk.session`.
export const clerkReady = clerk.load()

const IMPERSONATION_TOKEN_KEY = 'impersonationToken'

/**
 * Fresh Clerk session JWT for the Authorization header, or null when signed out.
 */
export async function getClerkToken() {
    try {
        await clerkReady
    } catch {
        // Clerk failed to load (e.g. misconfigured key): treat as signed out
        // rather than throwing inside every request.
        return null
    }
    if (!clerk.session) {
        return null
    }
    return clerk.session.getToken()
}

export function getImpersonationToken() {
    return sessionStorage.getItem(IMPERSONATION_TOKEN_KEY)
}

export function setImpersonationToken(token) {
    sessionStorage.setItem(IMPERSONATION_TOKEN_KEY, token)
}

export function clearImpersonationToken() {
    sessionStorage.removeItem(IMPERSONATION_TOKEN_KEY)
}

export default clerk
