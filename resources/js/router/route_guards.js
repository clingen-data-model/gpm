import store from '../store/index'

export const checkPermissionAndPersonOwnership = async (to) => {
    // Check for system permission
    if (store.getters.currentUser.hasPermission('people-manage')) {
        return true;
    }


    // if we don't have a currentItem in people store, get the person by the uuid
    if (!store.getters['people/currentItem'] || store.getters['people/currentItem'].uuid != to.params.uuid) {
        await store.dispatch('people/getPerson', {uuid: to.params.uuid})
    }

    // check that user is the same user associated with the person
    if (store.getters['people/currentItem'].user_id == store.getters.currentUser.id) {
        return true;
    }

    if (store.getters.currentUser.coordinatesPerson(store.getters['people/currentItem'])) {
        return true;
    }
    store.commit('pushError', 'You don\'t have permission to edit this person\'s information');

    return false;
};

export const hasGroupPermission = async (to, permission) => {
    if (store.getters.currentUser.hasPermission('groups-manage')) {
        return true;
    }

    if (!store.getters['groups/currentItem'] || store.getters['groups/currentItem'].uuid != to.params.uuid) {
        await store.dispatch('groups/findAndSetCurrent', to.params.uuid);
    }

    const group = store.getters['groups/currentItem'];

    if (store.getters.currentUser.hasGroupPermission(permission, group)) {
        return true;
    }

    store.commit('pushError', 'Permission denied');
    return false;
}

export const hasPermission = (to, permission) => {
    if (store.getters.currentUser.hasPermission(permission)) {
        return true;
    }

    store.commit('pushError', 'You don\'t have permission to access '+to.path)
    return false;
}

export const hasAnyPermission = (to, permissions) => {
    if (store.getters.currentUser.hasAnyPermission(permissions)) {
        return true;
    }
    store.commit('pushError', 'You don\'t have permission to access '+to.path)

    return false;
}
