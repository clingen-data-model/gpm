// import router from '.'
import store from '@/store/index'

const UserAdmin = () => import (/* user-admin */ '@/views/UserAdmin.vue')
const InviteAdmin = () => import (/* invite-admin */ '@/views/InviteAdmin.vue')


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
        component: () => import ('@/views/institutions/InstitutionList.vue')
    },
    {
        name: 'CredentialList',
        path: '/admin/credentials',
        component: () => import ('@/views/CredentialsList.vue')
    },
    {
        name: 'ExpertiseList',
        path: '/admin/expertises',
        component: () => import ('@/views/ExpertiseList.vue')
    },
]
