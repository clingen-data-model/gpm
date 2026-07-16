import axios from 'axios'
import {getImpersonationToken} from '@/composables/useImpersonationToken'

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

// Primary auth is the session cookie (withCredentials above). Two things
// still ride alongside it as headers:
//  - X-Impersonate-Token: honoured by ApplyImpersonationToken on top of the
//    session guard, same as the stateless-bearer-token design.
//  - Authorization: a live Clerk session token, needed only by the handful of
//    endpoints that verify Clerk directly because no GPM session exists yet
//    (invite redemption, the login exchange itself). Harmless elsewhere —
//    the session guard resolves the request from the cookie before anything
//    would look at this header.
api.interceptors.request.use(async (config) => {
    const impersonationToken = getImpersonationToken()
    if (impersonationToken) {
        config.headers['X-Impersonate-Token'] = impersonationToken
    }

    const token = await window.Clerk?.session?.getToken?.()?.catch(() => null)
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`
    }

    return config
})

export default api
