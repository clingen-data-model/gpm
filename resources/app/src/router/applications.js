const ApplicationIndex = () => import ( /* webpackChunkName: "application-index" */ '../views/ApplicationsIndex.vue')
const ApplicationDetail = () => import (/* webpackChunkName: "application-detail" */ '../views/applications/ApplicationDetail');
const CreateApplicationForm = () => import ( /* webpackChunkName: "create-application-form" */ '../components/applications/CreateApplicationForm');
const NextActionForm = () => import (/* next-action-form */ '../components/next_actions/NextActionForm')
const LogEntryForm = () => import (/* log-entry-form */ '../components/log_entries/LogEntryForm')

export default [
    { name: 'applications-index',
        path: '/applications',
        redirect: '/applications/vceps',
        components: {
            default: ApplicationIndex,
            modal: CreateApplicationForm,
        },
        children: [
            { name: 'gceps',
                path: "gceps",
                components: {
                        default: () => import ( /* webpackChunkName: "application-index" */ '../views/indexes/GcepsList.vue'),
                        modal: CreateApplicationForm,
                    }
            },
            { name: 'vceps',
                path: "vceps",
                components: {
                    default: () => import ( /* webpackChunkName: "application-index" */ '../views/indexes/VcepsList.vue'),
                    modal: CreateApplicationForm,
                }
            },
        ]
    },
    { name: 'ApplicationDetail',
        path: '/applications/:uuid',
        component: ApplicationDetail,
        props: true,
        children: [
            { name: 'NextAction',
                path: 'next-action',
                component: NextActionForm,
                meta: {
                    default: ApplicationDetail,
                    showModal: true
                },
                props: true,
            },
            { name: 'LogEntry',
                path: 'log-entry',
                component: LogEntryForm,
                meta: {
                    showModal: true
                },
                props: true,
            }
        ]
    },
]