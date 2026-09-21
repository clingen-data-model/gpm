import api from '@/http/api'

export const validateCode = async (code) => {
    try {
        await api.get(`/api/people/invites/${code}`)
        return true;
    } catch(error) {
        // eslint-disable-next-line no-console
        console.log(error);
        return false;
    }
}

export const fetchInvite = async (code) => {
    return await api.get(`/api/people/invites/${code}`)
        .then(response => {
            return response.data.data;
        })
}

export const redeemInvite = async (invite, email, password, password_confirmation) => {
    return api.put(`/api/people/invites/${invite.code}`, {email, password, password_confirmation})
}

export const redeemInviteForExistingUser = async (invite) => {
    return api.put(`/api/people/existing-user/invites/${invite.code}`);
}

/**
 * Redeem the invite with an existing identity-provider (ClinGen) account.
 * The endpoint lives in the web middleware group and starts the session,
 * so the CSRF cookie is fetched first. Callers handle their own errors.
 */
export const redeemInviteWithIdp = async (invite, token) => {
    await api.get('/sanctum/csrf-cookie')
    return api.put(`/api/people/invites/${invite.code}/idp`, {}, {
        headers: { Authorization: `Bearer ${token}` },
        skipErrorAlert: true,
    })
}
export default {
    fetchInvite
}
