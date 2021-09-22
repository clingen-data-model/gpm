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
                path: '/groups/:uuid/add-member',
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
