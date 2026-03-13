import axios from '@/http/api'
import { useAuthStore } from '@/stores/auth'
import { useAlertsStore } from '@/stores/alerts'

export function setupInterceptors() {
    axios.interceptors.request.use(
        config => {
            useAuthStore().addRequest()
            return config
        },
        error => {
            useAuthStore().removeRequest()
            return Promise.reject(error)
        },
    )

    axios.interceptors.response.use(
        response => {
            useAuthStore().removeRequest()
            return response
        },
        error => {
            useAuthStore().removeRequest()
            switch (error.response.status) {
                case 401:
                    useAuthStore().setAuthenticated(false)
                    return error
                case 403:
                    if (error.response.data.includes('The request to access this resource was rejected.')) {
                        const matches = error.response.data.match(/Reference this support identifier:\s*(\d+)/)
                        const supportId = matches[1] || null
                        useAlertsStore().pushError(`There is a Network Firewall issue.  Please contact support GPM Support ASAP at "gpm_support@clinicalgenome.org", providing details on your network connection and the following support ID: ${supportId}`)
                    } else {
                        useAlertsStore().pushError('You do not have permission to complete that action.  If you think this is an error please contact support at gpm_support@clinicalgenome.org')
                    }
                    return error
                case 404:
                    if (error.config.headers['X-Ignore-Missing'] !== '1') {
                        useAlertsStore().pushError('We couldn\'t find something you\'re looking for.')
                    }
                    return error
                case 500:
                    useAlertsStore().pushError('We\'ve encountered a problem trying to complete your request.  Support has been notified and we will be in touch.')
                    return error
            }
            return Promise.reject(error)
        },
    )
}
