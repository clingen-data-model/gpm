export default [
    { name: 'login',
        path: '/login',
        component: () => import ( '@/views/LoginPage.vue'),
    },
    {
        // Password reset is handled inside Clerk's prebuilt sign-in flow.
        name: 'reset-password',
        path: '/reset-password',
        redirect: { name: 'login' }
    }
]