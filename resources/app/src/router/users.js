import store from '@/store/index'

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
        props: true
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
        }
    }
]