import { createRouter, createWebHistory } from 'vue-router'
import store from '../store/index'

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
        name: 'index',
        components: {
            default: ApplicationIndex,
            modal: CreateApplicationForm,
        },
        children: [{
            path: "gceps",
            name: 'gceps',
            components: {
                    default: () => import ( /* webpackChunkName: "application-index" */ '../views/indexes/GcepsList.vue'),
                    modal: CreateApplicationForm,
                }
            },
            {
                path: "vceps",
                name: 'vceps',
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
        component: () => import ( '../views/LoginPage'),
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import ( /* webpackChunkName: "reset-password" */ '../views/ResetPassword')
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

router.beforeEach(async (to, from, next) => {
    await store.dispatch('checkAuth')

    if (to.name != 'login' && !store.state.authenticated) {
        console.log(to.name + '&&' + store.getters.isAuthed)
        next({name: 'login'});
        return;
    }

    if (to.name == 'login' && store.state.authenticated) {
        console.log(to.name + '&&' + store.getters.isAuthed)
        next({name: 'vceps'})
    }

    next();
})

export default router