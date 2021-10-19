import api from '@/http/api'
// import queryStringFromParams from '@/http/query_string_from_params'

export const validateCode = async (code) => {
    try {
        await api.get(`/api/people/invites/${code}`)
        return true;
    } catch(error) {
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

export default {
    fetchInvite
}