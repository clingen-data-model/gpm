const ApplicationIndex = () =>
    import ( /* webpackChunkName: "application-index" */ '@/views/ApplicationsIndex.vue')
const ApplicationDetail = () =>
    import ( /* webpackChunkName: "application-detail" */ '@/views/applications/ApplicationDetail');
const CreateApplicationForm = () =>
    import ( /* webpackChunkName: "create-application-form" */ '@/components/applications/CreateApplicationForm');
const NextActionForm = () =>
    import ( /* webpackChunkName: "next-action-form" */ '@/components/next_actions/NextActionForm')
const LogEntryForm = () =>
    import ( /* webpackChunkName: "log-entry-form" */ '@/components/log_entries/LogEntryForm')
const NewContactForm = () =>
    import ( /* webpackChunkName: "new-contact-form" */ '@/components/contacts/NewContactForm')
const ConfirmDeleteLogEntry = () =>
    import ( /* webpackChunkName: "confirm-delete-log-entry" */ '@/components/log_entries/ConfirmDeleteLogEntry')
const ConfirmDeleteNextAction = () =>
    import ( /* webpackChunkName: "confirm-delete-log-entry" */ '@/components/next_actions/ConfirmDeleteNextAction')


export default [{
        name: 'ApplicationsIndex',
        path: '/applications',
        redirect: '/applications/vceps',
        components: {
            default: ApplicationIndex,
            modal: CreateApplicationForm,
        },
        meta: {
            protected: true
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
                    protected: true
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
                    protected: true
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
                    title: 'Add Next Action'
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
                    title: 'Add Next Action'
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
                    title: 'Add Log Entry'
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
                },
                props: true
            },
            {
                name: 'AddContact',
                path: 'add-contact',
                component: NewContactForm,
                meta: {
                    showModal: true,
                    protected: true
                }
            }
        ]
    },
]