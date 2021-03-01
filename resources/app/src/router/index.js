import { createRouter, createWebHistory } from 'vue-router'
// import store from '../store/index'

const NextActionForm = () => import (/* next-action-form */ '../components/next_actions/NextActionForm')
const LogEntryForm = () => import (/* log-entry-form */ '../components/log_entries/LogEntryForm')
const PersonDetail = () => import (/* person-detail */ '../components/people/PersonDetail')
const PeopleIndex = () => import (/* people-index */ '../views/PeopleIndex')

const ApplicationIndex = () => import ( /* webpackChunkName: "application-index" */ '../views/ApplicationsIndex.vue')
const ApplicationDetail = () => import (/* webpackChunkName: "application-detail" */ '../views/applications/ApplicationDetail');
const CreateApplicationForm = () => import ( /* webpackChunkName: "create-application-form" */ '../components/applications/CreateApplicationForm');

const routes = [{
        path: '/',
        redirect: '/vceps',
        name: 'Index',
        components: {
            default: ApplicationIndex,
            modal: CreateApplicationForm,
        },
        children: [{
            path: "gceps",
            components: {
                    default: () => import ( /* webpackChunkName: "application-index" */ '../views/indexes/GcepsList.vue'),
                    modal: CreateApplicationForm,
                }
            },
            {
                path: "vceps",
                components: {
                    default: () => import ( /* webpackChunkName: "application-index" */ '../views/indexes/VcepsList.vue'),
                    modal: CreateApplicationForm,
                }
            },
        ]
    },
    {
        name: 'ApplicationDetail',
        path: '/applications/:uuid',
        component: ApplicationDetail,
        props: true,
        children: [{
                name: 'NextAction',
                path: 'next-action',
                component: NextActionForm,
                meta: {
                    default: ApplicationDetail,
                    showModal: true
                },
                props: true,
            },
            {
                name: 'LogEntry',
                path: 'log-entry',
                component: LogEntryForm,
                meta: {
                    showModal: true
                },
                props: true,
            }
        ]
    },
    {
        path: '/cdwgs',
        name: 'cdwg-index',
        component: () => import (/* webpackChunkName "cdwgs" */ '../views/cdwgs/CdwgIndex')
    },
    {
            name: 'people-index',
            path: '/people',
            component: PeopleIndex,
    },
    {
        path: '/people/:uuid',
        component: PersonDetail,
        name: 'person-detail',
        children: [
        ]

    },
    {
        path: '/about',
        name: 'about',
        component: () =>
            import ( /* webpackChunkName: "about" */ '../views/About.vue')
    },
    {
        path: '/login',
        name: 'login',
        component: () => import ( '../views/Login'),
    },
    {
        path: '/me',
        name: 'me',
        component: () => import ('../views/Me.vue'),
    }
]

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes
})

export default router