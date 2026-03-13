import { defineStore } from 'pinia'
import { v4 as uuid4 } from 'uuid'

export class Alert {
    static types = ['info', 'success', 'warning', 'error']

    constructor(message, type = 'info') {
        this.message = message
        if (Alert.types.findIndex(t => t === type) === -1) {
            throw new Error(`Unknown alert type: ${type}`)
        }
        this.type = type
        this.uuid = uuid4()
    }
}

export const useAlertsStore = defineStore('alerts', {
    state: () => ({
        alerts: [],
    }),
    getters: {
        info: state => state.alerts.filter(a => a.type === 'info'),
        success: state => state.alerts.filter(a => a.type === 'success'),
        warning: state => state.alerts.filter(a => a.type === 'warning'),
        error: state => state.alerts.filter(a => a.type === 'error'),
    },
    actions: {
        pushAlert({ message, type }) {
            this.alerts.push(new Alert(message, type))
        },
        pushError(message) {
            this.alerts.push(new Alert(message, 'error'))
        },
        pushInfo(message) {
            this.alerts.push(new Alert(message, 'info'))
        },
        pushSuccess(message) {
            this.alerts.push(new Alert(message, 'success'))
        },
        pushWarning(message) {
            this.alerts.push(new Alert(message, 'warning'))
        },
        removeAlert(uuid) {
            const idx = this.alerts.findIndex(a => a.uuid === uuid)
            if (idx < 0) return
            this.alerts.splice(idx, 1)
        },
        unshiftAlert({ message, type }) {
            this.alerts.unshift(new Alert(message, type))
        },
    },
})
