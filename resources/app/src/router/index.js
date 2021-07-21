import { createRouter, createWebHistory } from 'vue-router'
import store from '../store/index'
import applicationRoutes from './applications'
import peopleRoutes from './people'
import authRoutes from './auth'
import cdwgRoutes from './cdwgs'

const routes = [
    { name: 'home',
        path: '/',
        redirect: '/applications',
        meta: {
            protected: true
        }
    },
    {
        name: 'ApplicationSummary',
        path: '/application-summary',
        redirect: '/application-summary/vceps',
        component: () => import (/* webpackChunkName "applications-summary" */ '@/views/ApplicationSummary'),
        children: [{
                name: 'GcepsSummary',
                path: "gceps",
                component: () =>
                        import ( /* webpackChunkName: "application-summary" */ '@/views/SummaryGcep.vue'),
            },
            {
                name: 'VcepsSummary',
                path: "vceps",
                component: () =>
                        import ( /* webpackChunkName: "application-summary" */ '@/views/SummaryVcep.vue'),
            },
        ]
    },    
    ...applicationRoutes,
    ...peopleRoutes,
    ...authRoutes,
    ...cdwgRoutes,
    {
        name: 'coi',
        path: '/coi/:code',
        component: () => import (/* webpackChunkName "coi-survey" */ '@/views/Coi.vue'),
        props: true
    },
    {
        name: 'alt-coi',
        path: '/expert-panels/:name/coi/:code',
        component: () => import (/* webpackChunkName "coi-survey" */ '@/views/Coi.vue'),
        props: true
    },
    { name: 'about',
        path: '/about',
        component: () =>
            import ( /* webpackChunkName: "about" */ '@/views/About.vue')
    },
    {
        name: 'mail-log',
        path:'/mail-log',
        component: () => import(/* webpackChunkName "mail-log" */ '@/views/MailLog.vue'),
        meta: {
            protected: true
        }
    },
    {
        name: 'not-found',
        path: '/:pathMatch(.*)*',
        component: () => import (/* webpackChunkName "not-found" */ '@/views/NotFound.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes
})

router.beforeEach(async (to, from, next) => {
    if (!to.meta.protected) {
        next();
        return;
    }

    try{
        await store.dispatch('checkAuth')
        await store.dispatch('getCurrentUser');
    } catch (error) {
        console.log(error);
    }

    if (!to.name.includes('login') && !store.getters.isAuthed) {
        next({name: 'login'});
        return;
    }

    if (to.name == 'login' && store.getters.isAuthed) {
        next({name: 'home'})
        return;
    }

    next();
})


export default router