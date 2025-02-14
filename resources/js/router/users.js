import store from '@/store/index'

const hasPermission = async (permission) => {
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
            default: () => import ('@/views/users/UserList.vue'),
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
            default: () => import ('@/views/users/UserDetail.vue')
        },
        props: true,
        meta: {
            protected: true
        },
        async beforeEnter () {
            return hasPermission('users-manage');
        }
    }
]