import axios from 'axios'

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

// Attach Clerk session token when available. window.Clerk is populated by
// @clerk/vue after the plugin initialises. When VITE_CLERK_PUBLISHABLE_KEY is
// not set (local dev without Clerk) this interceptor is a no-op and the
// existing Sanctum session cookie handles authentication instead.
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