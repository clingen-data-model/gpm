import store from '../store/index'

export const checkPermissionAndPersonOwnership = async (to) => {
    // Check for system permission
    if (store.getters.currentUser.hasPermission('people-manage')) {
        return true;
    }


    // if we don't have a currentItem in people store, get the person by the uuid
    if (!store.getters['people/currentItem'] || store.getters['people/currentItem'].uuid !== to.params.uuid) {
        await store.dispatch('people/getPerson', {uuid: to.params.uuid})
    }

    // check that user is the same user associated with the person
    if (store.getters['people/currentItem'].user_id === store.getters.currentUser.id) {
        return true;
    }

    if (store.getters.currentUser.coordinatesPerson(store.getters['people/currentItem'])) {
        return true;
    }
    store.commit('pushError', 'You don\'t have permission to edit this person\'s information');

    return false;
};

export const hasGroupPermission = async (to, permissions, bypassPermissions = ['groups-manage']) => {
    const requiredPermissions = Array.isArray(permissions) ? permissions : [permissions]
    const allowedBypassPermissions = Array.isArray(bypassPermissions) ? bypassPermissions : [bypassPermissions]

    if (allowedBypassPermissions.some(permission => store.getters.currentUser.hasPermission(permission))) 
    {
        return true
    }

    if (!store.getters['groups/currentItem'] || store.getters['groups/currentItem'].uuid !== to.params.uuid) 
    {
        await store.dispatch('groups/findAndSetCurrent', to.params.uuid)
    }

    const group = store.getters['groups/currentItem']

    const hasRequiredPermission = requiredPermissions.some(permission =>
        store.getters.currentUser.hasGroupPermission(permission, group)
    )

    if (hasRequiredPermission) {
        return true
    }

    store.commit('pushError', 'Permission denied')
    return false
}

export const hasPermission = (to, permission) => {
    if (store.getters.currentUser.hasPermission(permission)) {
        return true;
    }

    store.commit('pushError', `You don't have permission to access ${to.path}`)
    return false;
}

export const hasAnyPermission = (to, permissions) => {
    if (store.getters.currentUser.hasAnyPermission(permissions)) {
        return true;
    }
    store.commit('pushError', `You don't have permission to access ${to.path}`)

    return false;
}
