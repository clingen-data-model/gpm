export const cilogonEnabled = import.meta.env.VITE_CILOGON_ENABLED === 'true'

const errorMessages = {
    no_account: 'No GPM account is linked to that login. If you were invited, use the link in your invitation email; otherwise contact your group coordinator.',
    already_linked: 'That login is already linked to a different GPM account.',
    account_conflict: 'Another GPM account already uses this email address. Please contact support.',
    invite_invalid: 'That invitation is no longer valid. Please log in or contact your coordinator.',
    not_signed_in: 'Please log in before linking another login method.',
    cilogon_failed: 'Sign-in with CILogon did not complete. Please try again.',
}

export function cilogonErrorMessage(code) {
    return code ? (errorMessages[code] || errorMessages.cilogon_failed) : null
}

export function cilogonRedirectUrl({ idp, inviteCode, link } = {}) {
    const params = new URLSearchParams()
    if (idp) params.set('idp', idp)
    if (inviteCode) params.set('invite', inviteCode)
    if (link) params.set('link', '1')
    const qs = params.toString()
    return qs ? `/auth/cilogon/redirect?${qs}` : '/auth/cilogon/redirect'
}
