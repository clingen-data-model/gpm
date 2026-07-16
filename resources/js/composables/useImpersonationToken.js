const IMPERSONATION_TOKEN_KEY = 'impersonationToken'

export function getImpersonationToken() {
    return sessionStorage.getItem(IMPERSONATION_TOKEN_KEY)
}

export function setImpersonationToken(token) {
    sessionStorage.setItem(IMPERSONATION_TOKEN_KEY, token)
}

export function clearImpersonationToken() {
    sessionStorage.removeItem(IMPERSONATION_TOKEN_KEY)
}
