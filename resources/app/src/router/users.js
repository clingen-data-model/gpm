import store from '@/store/index'

const hasPermission = async (permission) => {
    console.log(permission);
    if (store.getters.currentUser.hasPermission(permission)) {
        return true;
    }

    store.commit('pushError', 'Permission denied');
    return false;
}

export default [
    {
        name: 'UserList',
        path: '/users',
        components:  {
            default: () => import ( /* webpackChunkName: "user-management" */ '@/views/users/UserList.vue'),
        },
        meta: {
            protected: true,
        },
        props: true,
        beforeEnter () {
            return hasPermission('users-manage');
        }
    },
    {
        name: 'UserDetail',
        path: '/users/:id',
        components: {
            default: () => import ( /* webpackChunkName: "user-management" */ '@/views/users/UserDetail.vue')
        },
        props: true,
        meta: {
            protected: true
        },
        async beforeEnter (to) {
            return hasPermission('users-manage');
        }
    }
]