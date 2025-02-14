import { hasGroupPermission } from './route_guards'

const MemberForm = () => import ('@/components/groups/MemberForm.vue')

export default [
    { name: 'GroupList',
        path: '/groups',
        components:  {
            default: () => import ('@/views/groups/GroupList.vue')
        },
        meta: {
            protected: true,
        },
        props: true
    },
    { name: 'GroupDetail',
        path: '/groups/:uuid',
        components: {
            default: () => import ('@/views/groups/GroupDetail.vue')
        },
        meta: {
            protected: true
        },
        props: true,

        children: [
            {
                name: 'AddMember',
                path: 'members/add',
                component: MemberForm,
                meta: {
                    // default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                beforeEnter: async (to) => {
                    return await hasGroupPermission(to, 'members-invite', ['groups-manage'])
                }
            },
            {
                name: 'EditMember',
                path: 'members/:memberId',
                component: MemberForm,
                meta: {
                    // default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                beforeEnter: async (to) => {
                    return await hasGroupPermission(to, 'members-update', ['groups-manage'])
                }
            },
        ],
    },
    { name: 'GroupApplication',
        path: '/groups/:uuid/application',
        components: {
            default: () => import ('@/views/groups/GroupApplication.vue')
        },
        meta: {
            protected: true
        },
        props: true,
        beforeEnter: async (to) => {
            return await hasGroupPermission(to, 'application-edit')
        },
        children: [
            {
                name: 'AppAddMember',
                path: 'members/add',
                component: MemberForm,
                meta: {
                    // default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                beforeEnter: async (to) => {
                    return await hasGroupPermission(to, 'members-invite', ['groups-manage'])
                }
            },
            {
                name: 'AppEditMember',
                path: 'members/:memberId',
                component: MemberForm,
                meta: {
                    // default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                beforeEnter: async (to) => {
                    return await hasGroupPermission(to, 'members-update', ['groups-manage'])
                }
            },
        ],
    },
    { name: 'ReviewApplication',
        path: '/groups/:uuid/application/review',
        component: () =>  import ('@/components/expert_panels/ApplicationResponse.vue'),
        props: true,
        meta: {
            protected: true
        }
    },
    { name: 'AnnualUpdate',
        path: '/groups/:uuid/annual-update',
        components: {
            default: () => import ('@/views/AnnualUpdateForm.vue')
        },
        meta: {
            protected: true
        },
        props: true,
        beforeEnter: async (to) => {
            return await hasGroupPermission(to, 'application-edit')
        },
        children: [
            {
                name: 'ReviewAddMember',
                path: 'members/add',
                component: MemberForm,
                meta: {
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                beforeEnter: async (to) => {
                    return await hasGroupPermission(to, 'members-invite', ['groups-manage'])
                }
            },
            {
                name: 'ReviewEditMember',
                path: 'members/:memberId',
                component: MemberForm,
                meta: {
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                beforeEnter: async (to) => {
                    return await hasGroupPermission(to, 'members-update', ['groups-manage'])
                }
            },
        ],
    },
    { name: 'SustainedCurationReview',
        path: '/groups/:uuid/sustained-curation-review',
        components: {
            default: () => import('@/views/groups/SustainedCurationReview.vue')
        },
        meta: {
            protected: true
        },
        props: true,
        beforeEnter: async (to) => {
            return await hasGroupPermission(to, 'application-edit')
        },
    },
]
