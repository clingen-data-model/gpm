export default [
    { name: 'login',
        path: '/login',
        component: () => import ( '@/views/LoginPage'),
    },
    { name: 'reset-password',
        path: '/reset-password',
        component: () => import ( /* webpackChunkName: "reset-password" */ '@/views/ResetPassword')
    }    
]