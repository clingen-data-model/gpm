import axios from 'axios'

const api = axios.create({
    baseURL: '',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        common: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    },
});

// Attach the Clerk session token to every request. window.Clerk is populated
// by @clerk/vue after plugin initialisation.
api.interceptors.request.use(async (config) => {
    const session = window.Clerk?.session
    if (session) {
        const token = await session.getToken()
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
    }
    return config
})

export default api