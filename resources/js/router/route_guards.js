import { useAuthStore } from '@/stores/auth'
import { useAlertsStore } from '@/stores/alerts'
import { usePeopleStore } from '@/stores/people'
import { useGroupsStore } from '@/stores/groups'

export const checkPermissionAndPersonOwnership = async (to) => {
    const authStore = useAuthStore();
    const alertsStore = useAlertsStore();
    const peopleStore = usePeopleStore();

    // Check for system permission
    if (authStore.currentUser.hasPermission('people-manage')) {
        return true;
    }


    // if we don't have a currentItem in people store, get the person by the uuid
    if (!peopleStore.currentItem || peopleStore.currentItem.uuid !== to.params.uuid) {
        await peopleStore.getPerson({uuid: to.params.uuid})
    }

    // check that user is the same user associated with the person
    if (peopleStore.currentItem.user_id === authStore.currentUser.id) {
        return true;
    }

    if (authStore.currentUser.coordinatesPerson(peopleStore.currentItem)) {
        return true;
    }
    alertsStore.pushError('You don\'t have permission to edit this person\'s information');

    return false;
};

export const hasGroupPermission = async (to, permission) => {
    const authStore = useAuthStore();
    const alertsStore = useAlertsStore();
    const groupsStore = useGroupsStore();

    if (authStore.currentUser.hasPermission('groups-manage')) {
        return true;
    }

    if (!groupsStore.currentItem || groupsStore.currentItem.uuid !== to.params.uuid) {
        await groupsStore.findAndSetCurrent(to.params.uuid);
    }

    const group = groupsStore.currentItem;

    if (authStore.currentUser.hasGroupPermission(permission, group)) {
        return true;
    }

    alertsStore.pushError('Permission denied');
    return false;
}

export const hasPermission = (to, permission) => {
    const authStore = useAuthStore();
    const alertsStore = useAlertsStore();

    if (authStore.currentUser.hasPermission(permission)) {
        return true;
    }

    alertsStore.pushError(`You don't have permission to access ${to.path}`)
    return false;
}

export const hasAnyPermission = (to, permissions) => {
    const authStore = useAuthStore();
    const alertsStore = useAlertsStore();

    if (authStore.currentUser.hasAnyPermission(permissions)) {
        return true;
    }
    alertsStore.pushError(`You don't have permission to access ${to.path}`)

    return false;
}
