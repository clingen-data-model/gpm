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

console.log(store.state);

router.beforeEach(async (to, from, next) => {

    console.info('beforeEach('+to.name+', '+from.name+') - store.state.authenticated', store.getters.isAuthed)
    if (to.name != 'login' && !store.getters.isAuthed) {
        next({name: 'login'});
        return;
    }

    if (to.name == 'login' && store.getters.isAuthed) {
        next({name: 'home'})
    }

    next();
})


export default router