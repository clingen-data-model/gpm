const GroupList = () =>
    import ( /* webpackChunkName: "group-list" */ '@/views/groups/GroupList.vue')
const GroupDetail = () =>
    import ( /* webpackChunkName: "group-detail" */ '@/views/groups/GroupDetail.vue')
const MemberForm = () =>
    import ( /* webpackChunkName: "group-detail" */ '@/components/groups/MemberForm.vue')

export default [
    {
        name: 'GroupList',
        path: '/groups',
        components:  {
            default: GroupList
        },
        meta: {
            protected: true,
        },
        props: true
    },
    {
        name: 'GroupDetail',
        path: '/groups/:uuid',
        components: {
            default: GroupDetail
        },
        meta: {
            protected: true
        },
        props: true,

        children: [
            {
                name: 'AddMember',
                path: '/groups/:uuid/members/add',
                component: MemberForm,
                meta: {
                    default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
            },
            {
                name: 'EditMember',
                path: '/groups/:uuid/members/:memberId',
                component: MemberForm,
                meta: {
                    default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
            },
        ],
    },
]
