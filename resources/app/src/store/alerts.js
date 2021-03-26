import {v4 as uuid4} from 'uuid'

class Alert {
    static types = [
        'info',
        'success',
        'warning',
        'error'
    ];

    constructor(message, type='info') {
        this.message = message;
        if (Alert.types.findIndex(t => t == type) == -1)  {
            throw new Error('Unknown alert type: '+type)
        }
        this.type = type
        this.uuid = uuid4()
    }
}

export default {
    state: {
        alerts: [],
    },
    getters: {
        info: state => state.alerts.filter(a => a.type == 'info'),
        success: state => state.alerts.filter(a => a.type == 'success'),
        warning: state => state.alerts.filter(a => a.type == 'warning'),
        error: state => state.alerts.filter(a => a.type == 'error'),
    },
    mutations: {

        pushAlert(state, {message, type}) {
            state.alerts.push(new Alert(message, type));
        },
        removeAlert(state, uuid) {
            const idx = state.alerts.findIndex(a => a.uuid == uuid);
            if (idx < 0) {
                return;
            }
            state.alerts.splice(idx,1)
        },
        unshiftAlert(state, {message, type}) {
            state.alerts.unshift(new Alert(message, type));
        }
    }
}