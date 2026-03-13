import { defineStore } from 'pinia'
import axios from '@/http/api'
import isAuthError from '@/http/is_auth_error'
import User from '@/domain/user'

let currentUserPromise = null

export const useAuthStore = defineStore('auth', {
    state: () => ({
        hostname: import.meta.env.VITE_APP_URL,
        user: new User(),
        openRequests: 0,
        authenticating: true,
        authenticated: null,
        documentTypes: null,
        features: {
            legacyCoi: import.meta.env.VITE_APP_LEGACY_COI === 'true',
        },
        systemInfo: { build: {}, app: { features: {} } },
    }),
    getters: {
        currentUser: state => state.user,
        authed: state => state.authenticated,
        isAuthed: state => state.authenticated,
    },
    actions: {
        addRequest() {
            this.openRequests++
        },
        removeRequest() {
            this.openRequests--
        },
        setCurrentUser(user) {
            this.user = new User(user)
            this.authenticated = true
        },
        clearCurrentUser() {
            this.user = new User()
            this.authenticated = false
        },
        setAuthenticated(authVal) {
            this.authenticated = authVal
        },
        startAuthenticating() {
            this.authenticating = true
        },
        stopAuthenticating() {
            this.authenticating = false
        },
        async getCurrentUser() {
            if (currentUserPromise) {
                return currentUserPromise
            }
            if (this.authenticated && this.user.id !== null) {
                return Promise.resolve()
            }
            return this.forceGetCurrentUser()
        },
        async forceGetCurrentUser() {
            currentUserPromise = (async () => {
                try {
                    const response = await axios.get('/api/current-user')
                    this.setCurrentUser(response.data.data)
                    this.getSystemInfo()
                } catch (error) {
                    // eslint-disable-next-line no-console
                    console.log(error)
                    this.clearCurrentUser()
                }
            })()

            const localPromise = currentUserPromise
            localPromise.finally(() => {
                if (currentUserPromise === localPromise) {
                    currentUserPromise = null
                }
            })

            return currentUserPromise
        },
        async login({ email, password }) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/login', { email, password })
                .then(() => {
                    this.setAuthenticated(true)
                    this.getCurrentUser()
                })
        },
        async logout() {
            try {
                await axios.post('/api/logout')
                    .then(() => {
                        this.clearCurrentUser()
                    })
            } catch (error) {
                if (isAuthError(error)) {
                    // eslint-disable-next-line no-alert
                    alert('You cannot log out because you are not logged in')
                }
            }
        },
        async checkAuth() {
            if (!this.authenticated) {
                await axios.get('/api/authenticated')
                    .then(() => {
                        this.setAuthenticated(true)
                    })
                    .catch(error => {
                        if (error.response.status && error.response.status === 401) {
                            this.setAuthenticated(false)
                        }
                    })
            }
        },
        getSystemInfo() {
            if (this.authenticated) {
                axios.get('/api/system-info')
                    .then(response => {
                        this.systemInfo = response.data
                    })
            }
        },
    },
})
