import store from '@/store/index'

export const userCan = (permission, group) => store.state.user.hasPermission(permission, group);
export const hasPermission = (permission, group) => store.state.user.hasPermission(permission, group);
export const hasAnyPermission = (permissions) => store.state.user.hasAnyPermission(permissions);
export const hasRole = (role, group) => store.state.user.hasRole(role, group);
export const userInGroup = (group) => {
    return store.state.user.person
            .memberships
            .map(m => m.group_id)
            .includes(group.id)
};
export const userIsPerson = (person) => {
    return store.state.user.id == person.user_id;
}
