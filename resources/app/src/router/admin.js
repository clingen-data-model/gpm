// import router from '.'
import store from '@/store/index'

const UserAdmin = () => import (/* user-admin */ '@/views/UserAdmin')
const InviteAdmin = () => import (/* invite-admin */ '@/views/InviteAdmin')


export default [
    {name: 'UserAdmin',
        path: '/admin/users',
        component: UserAdmin,
        props: true,
        meta: {
            protected: true,
            beforeEnter: async () => {
                return await store.getters.currentUser.hasPermission('users-manage')
            }
        }
    },
    {
        name: 'InviteAdmin',
        path: '/admin/invites',
        component: InviteAdmin,
        props: true,
        meta: {
            protected: true,
            beforeEnter: async () => {
                return await store.getters.currentUser.hasPermission('people-manage')
            }
        }
    },
    {
        name: 'InstitutionList',
        path: '/admin/institutions',
        component: () => import ( /* webpackChunkName: "institution-list" */ '@/views/institutions/InstitutionList.vue')
}
]