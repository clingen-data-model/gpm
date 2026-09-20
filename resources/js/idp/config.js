/**
 * Identity-provider settings for the SPA.
 *
 * Injected into the page by ViewController (window.__GPM_IDP__) because the
 * built assets are shared across environments and the login page needs the
 * driver before any session exists. VITE_* values are a fallback for vitest
 * and vite-only runs.
 */
const DEFAULT = { driver: 'null', clerk: null }

export function getIdpConfig() {
    const injected = typeof window !== 'undefined' ? window.__GPM_IDP__ : null
    if (injected && typeof injected === 'object' && injected.driver) {
        return { ...DEFAULT, ...injected }
    }

    const driver = import.meta.env.VITE_IDP_DRIVER || DEFAULT.driver
    return {
        driver,
        clerk: driver === 'clerk'
            ? { publishableKey: import.meta.env.VITE_CLERK_PUBLISHABLE_KEY || '' }
            : null,
    }
}

export function idpDriver() {
    return getIdpConfig().driver
}

export function idpEnabled() {
    return idpDriver() !== 'null'
}
