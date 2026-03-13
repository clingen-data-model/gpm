import { useAuthStore } from '@/stores/auth'

export const userCan = (permission, group) => useAuthStore().user.hasPermission(permission, group);
export const hasPermission = (permission, group) => useAuthStore().user.hasPermission(permission, group);
export const hasAnyPermission = (permissions) => useAuthStore().user.hasAnyPermission(permissions);
export const hasRole = (role, group) => useAuthStore().user.hasRole(role, group);
export const userInGroup = (group) => {
    return useAuthStore().user.person
            .memberships
            .map(m => m.group_id)
            .includes(group.id)
};
export const userIsPerson = (person) => {
    return useAuthStore().user.id === person.user_id;
}
export const coordinatesPerson = (person)  => useAuthStore().user.coordinatesPerson(person);
