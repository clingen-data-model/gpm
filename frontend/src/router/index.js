import { createRouter, createWebHistory } from 'vue-router'
import ApplicationsIndex from '../views/ApplicationsIndex.vue'
import GcepsList from '../views/indexes/GcepsList.vue'
import VcepsList from '../views/indexes/VcepsList.vue'
import ApplicationDetail from '../views/applications/ApplicationDetail'
import CreateApplicationForm from '../components/applications/CreateApplicationForm'

const routes = [{
        path: '/',
        redirect: '/vceps',
        name: 'Index',
        components: {
            default: ApplicationsIndex,
            modal: CreateApplicationForm,
        },
        children: [{
                path: "gceps",
                components: {
                    default: GcepsList,
                    modal: CreateApplicationForm
                }
            },
            {
                path: "vceps",
                components: {
                    default: VcepsList,
                    modal: CreateApplicationForm
                }
            },
        ]
    },
    {
        name: 'ApplicationDetail',
        path: '/applications/:uuid',
        component: ApplicationDetail,
        props: true
    },
    {
        path: '/about',
        name: 'About',
        component: () =>
            import ( /* webpackChunkName: "about" */ '../views/About.vue')
    }
]

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes
})

export default router