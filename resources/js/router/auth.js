export default [
    { name: 'login',
        path: '/login',
        component: () => import ( '@/views/LoginPage.vue'),
    },
    { name: 'reset-password',
        path: '/reset-password',
        component: () => import ('@/views/ResetPassword.vue')
    }    
]