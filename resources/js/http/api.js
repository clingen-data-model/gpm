import axios from 'axios'
import { getClerkToken, getImpersonationToken } from '@/clerk'

const api = axios.create({
    baseURL: '',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        common: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    },

});

// Attach the Clerk session token as a Bearer to every request. When an
// impersonation token is present it is sent too; the backend guard prefers it
// and resolves the request as the impersonated user.
api.interceptors.request.use(async (config) => {
    const impersonationToken = getImpersonationToken()
    if (impersonationToken) {
        config.headers['X-Impersonate-Token'] = impersonationToken
    }

    const token = await getClerkToken()
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`
    }

    return config
})

export default api