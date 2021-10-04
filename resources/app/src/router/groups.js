const GroupDetail = () =>
    import ( /* webpackChunkName: "group-detail" */ '@/views/groups/GroupDetail.vue')
const AddMemberForm = () =>
    import ( /* webpackChunkName: "group-detail" */ '@/components/groups/AddMemberForm.vue')

export default [
    {
        name: 'GroupDetail',
        path: '/groups/:uuid',
        components: {
            default: GroupDetail
        },
        meta: {
            protected: false
        },
        props: true,

        children: [
            {
                name: 'AddMember',
                path: '/groups/:uuid/members/add',
                component: AddMemberForm,
                meta: {
                    default: GroupDetail,
                    showModal: true,
                    protected: false,
                    title: 'Add Group Member'
                },
                props: true,
            },
            {
                name: 'EditMember',
                path: '/groups/:uuid/members/:memberId',
                component: AddMemberForm,
                meta: {
                    default: GroupDetail,
                    showModal: true,
                    protected: false,
                    title: 'Add Group Member'
                },
                props: true,
            },
        ],
    },
]
