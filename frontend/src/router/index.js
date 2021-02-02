import { createRouter, createWebHistory } from 'vue-router'
import ApplicationsIndex from '../views/ApplicationsIndex.vue'
import GcepsList from '../views/indexes/GcepsList.vue'
import VcepsList from '../views/indexes/VcepsList.vue'
import ApplicationDetail from '../views/applications/ApplicationDetail'

const routes = [{
        path: '/',
        name: 'Index',
        component: ApplicationsIndex,
        children: [{
                path: "gceps",
                component: GcepsList,
            },
            {
                path: "vceps",
                component: VcepsList
            }
        ]
    },
    {
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