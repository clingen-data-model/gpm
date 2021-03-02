import { createRouter, createWebHistory } from 'vue-router'
import store from '../store/index'
import applicationRoutes from './applications'
import peopleRoutes from './people'
import authRoutes from './auth'
import cdwgRoutes from './cdwgs'

const routes = [
    { name: 'home',
        path: '/',
        redirect: '/applications'
    },
    ...applicationRoutes,
    ...peopleRoutes,
    ...authRoutes,
    ...cdwgRoutes,
    { name: 'about',
        path: '/about',
        component: () =>
            import ( /* webpackChunkName: "about" */ '../views/About.vue')
    },
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