const ApplicationIndex = () =>
    import ( /* webpackChunkName: "application-index" */ '@/views/ApplicationsIndex.vue')
const ApplicationDetail = () =>
    import ( /* webpackChunkName: "application-detail" */ '@/views/applications/ApplicationDetail.vue');
const CreateApplicationForm = () =>
    import ( /* webpackChunkName: "create-application-form" */ '@/components/applications/CreateApplicationForm.vue');
const NextActionForm = () =>
    import ( /* webpackChunkName: "next-action-form" */ '@/components/next_actions/NextActionForm.vue')
const LogEntryForm = () =>
    import ( /* webpackChunkName: "log-entry-form" */ '@/components/log_entries/LogEntryForm.vue')
const MemberForm = () =>
    import ( /* webpackChunkName: "new-contact-form" */ '@/components/groups/MemberForm.vue')
const ConfirmDeleteLogEntry = () =>
    import ( /* webpackChunkName: "confirm-delete-log-entry" */ '@/components/log_entries/ConfirmDeleteLogEntry.vue')
const ConfirmDeleteNextAction = () =>
    import ( /* webpackChunkName: "confirm-delete-log-entry" */ '@/components/next_actions/ConfirmDeleteNextAction.vue')
const ConfirmDeleteApplication = () =>
    import ( /* webpackChunkName: "delete-application" */ '@/components/applications/ConfirmDeleteApplication.vue')


export default [{
        name: 'ApplicationsIndex',
        path: '/applications',
        redirect: '/applications/vceps',
        components: {
            default: ApplicationIndex,
            modal: CreateApplicationForm,
        },
        meta: {
            protected: true,
            permissions: ['ep-applications-manage']
        },
        children: [{
                name: 'gceps',
                path: "gceps",
                components: {
                    default: () =>
                        import ( /* webpackChunkName: "application-index" */ '@/views/indexes/GcepsList.vue'),
                    modal: CreateApplicationForm,
                },
                meta: {
                    protected: true,
                    permissions: ['ep-applications-manage']
                },
            },
            {
                name: 'vceps',
                path: "vceps",
                components: {
                    default: () =>
                        import ( /* webpackChunkName: "application-index" */ '@/views/indexes/VcepsList.vue'),
                    modal: CreateApplicationForm,
                },
                meta: {
                    protected: true,
                    permissions: ['ep-applications-manage']
                }
            },
        ]
    },
    {
        name: 'ApplicationDetail',
        path: '/applications/:uuid',
        component: ApplicationDetail,
        props: true,
        meta: {
            protected: true
        },
        children: [{
                name: 'NextAction',
                path: 'next-action',
                component: NextActionForm,
                meta: {
                    default: ApplicationDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Next Action',
                    permissions: ['ep-applications-manage']
                },
                props: true,
            },
            {
                name: 'EditNextAction',
                path: 'next-actions/:id/edit',
                component: NextActionForm,
                meta: {
                    default: ApplicationDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Next Action',
                    permissions: ['ep-applications-manage']
                },
                props: true,
            },
            {
                name: 'ConfirmDeleteNextAction',
                path: 'next-actions/:id/delete',
                component: ConfirmDeleteNextAction,
                meta: {
                    showModal: true,
                    protected: true,
                    permissions: ['ep-applications-manage']
                },
                props: true
            },
            {
                name: 'LogEntry',
                path: 'log-entry',
                component: LogEntryForm,
                meta: {
                    showModal: true,
                    protected: true,
                    permissions: ['ep-applications-manage'],
                    title: 'Add Log Entry',
                },
                props: true,
            },
            {
                name: 'EditLogEntry',
                path: 'log-entries/:id/edit',
                component: LogEntryForm,
                meta: {
                    showModal: true,
                    protected: true,
                    permissions: ['ep-applications-manage'],
                    title: 'Edit Log Entry'
                },
                props: true
            },
            {
                name: 'ConfirmDeleteLogEntry',
                path: 'log-entries/:id/delete',
                component: ConfirmDeleteLogEntry,
                meta: {
                    showModal: true,
                    protected: true,
                    permissions: ['ep-applications-manage'],
                },
                props: true
            },
            {
                name: 'AddMemberToApplication',
                path: 'members/add',
                component: MemberForm,
                meta: {
                    title: 'Add Group Member',
                    showModal: true,
                    protected: true,
                    permissions: ['ep-applications-manage'],
                },
                props: true
            },
            {
                name: 'EditMemberOnApplication',
                path: 'members/:memberId',
                component: MemberForm,
                meta: {
                    // default: GroupDetail,
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true
            },
            {
                name: 'ConfirmDeleteApplication',
                path: 'delete',
                component: ConfirmDeleteApplication,
                props: true,
                meta: {
                    default: ApplicationDetail,
                    showModal: true,
                    protected: true,
                    permissions: ['ep-applications-manage'],
                }
            }
        ]
    },
]