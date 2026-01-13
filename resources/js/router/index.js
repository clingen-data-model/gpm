import { createRouter, createWebHistory } from 'vue-router'
import store from '../store/index'
import applicationRoutes from './applications'
import peopleRoutes from './people'
import authRoutes from './auth'
import cdwgRoutes from './cdwgs'
import groupRoutes from './groups'
import adminRoutes from './admin'
import userRoutes from './users'
import fundingSourceRoutes from './funding-sources'


const routes = [
    {
        name: 'Dashboard',
        path: '/',
        component: () => import('@/views/Dashboard.vue'),
        meta: {
            protected: true
        }
    },
    {
        name: 'ApplicationSummary',
        path: '/application-summary',
        redirect: '/application-summary/vceps',
        component: () => import('@/views/ApplicationSummary.vue'),
        children: [{
            name: 'GcepsSummary',
            path: "gceps",
            component: () =>
                import('@/views/SummaryGcep.vue'),
        },
        {
            name: 'VcepsSummary',
            path: "vceps",
            component: () =>
                import('@/views/SummaryVcep.vue'),
        },
        ]
    },
    {
        name: 'AnnualUpdatesList',
        path: '/annual-updates',
        component: () => import('@/views/AnnualUpdatesList.vue'),
        meta: {
            protected: true,
            permissions: ['annual-updates-manage']
        },
    },
    {
        name: 'AnnualUpdateDetail',
        path: '/annual-updates/:id',
        component: () => import('@/views/AnnualUpdateDetail.vue'),
        props: true,
        meta: {
            protected: true,
        },
        children: [
            {
                name: 'AnnualUpdateAddMember',
                path: 'members/add',
                component: () => import('@/components/groups/MemberForm.vue'),
                meta: {
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                // beforeEnter: async (to) => {
                //     return store.getters.currentUser.hasPermission('annual-updates-manage');
                // }
            },
            {
                name: 'AnnualUpdateEditMember',
                path: 'members/:memberId',
                component: () => import('@/components/groups/MemberForm.vue'),
                meta: {
                    showModal: true,
                    protected: true,
                    title: 'Add Group Member'
                },
                props: true,
                // beforeEnter: async (to) => {
                //     return store.getters.currentUser.hasPermission('annual-updates-manage');
                // }
            },
        ],
    },
    ...applicationRoutes,
    ...groupRoutes,
    ...peopleRoutes,
    ...authRoutes,
    ...cdwgRoutes,
    ...adminRoutes,
    ...userRoutes,
    ...fundingSourceRoutes,
    {
        name: 'CoiNoCode',
        path: '/coi',
        redirect: '/'
    },
    {
        name: 'coi',
        path: '/coi/:code',
        component: () => import('@/views/Coi.vue'),
        props: true,
        meta: {
            protected: true
        }
    },
    {
        name: 'alt-coi',
        path: '/expert-panels/:name/coi/:code',
        component: () => import('@/views/Coi.vue'),
        props: true,
        meta: {
            protected: true
        }
    },
    {
        name: 'about',
        path: '/about',
        component: () =>
            import('@/views/About.vue')
    },
    {
        name: 'PendingCoiList',
        path: '/onboarding/cois',
        component: () => import('@/views/PendingCoiList.vue'),
        props: true,
        meta: {
            protected: true
        }

    },
    {
        name: 'mail-log',
        path: '/mail-log',
        component: () => import('@/views/MailLog.vue'),
        meta: {
            protected: true
        }
    },
    {
        name: 'not-found',
        path: '/:pathMatch(.*)*',
        component: () => import('@/views/NotFound.vue'),
    },
    {
        name: 'reports',
        path: '/reports',
        component: () => import('@/views/Reports.vue'),
        meta: {
            protected: true,
            permissions: ['reports-pull']
        },
        // beforeEnter: (to) => hasPermission(to, 'reports-pull')
    },
]

const router = createRouter({
    history: createWebHistory(import.meta.env.VITE_BASE_URL || '/'),
    routes
})


/**
 * Auth checks before all protected routes.
 */
router.beforeEach(async (to, from, next) => {
    if (!to.meta.protected) {
        next();
        return;
    }

    try {
        await store.dispatch('checkAuth')
        await store.dispatch('getCurrentUser');
    } catch (error) {
        // eslint-disable-next-line no-console
        console.log(error);
    }

    if (!to.name.includes('login') && !store.getters.isAuthed) {
        next({ name: 'login', query: { redirect: to.fullPath } });
        return;
    }

    if (to.name === 'login' && store.getters.isAuthed) {
        next({ name: 'Dashboard' })
        return;
    }

    if (to.meta.permissions && Array.isArray(to.meta.permissions) && to.meta.permissions.length > 0) {
        if (store.getters.currentUser.hasAnyPermission(to.meta.permissions)) {
            next();
            return;
        }
        router.replace({ name: 'Dashboard' })
        store.commit('pushError', `You don't have permission to access ${to.path}`)
    }

    next();
})

/**
 * Required data checks before protected routes.
 */
router.beforeEach(async (to, from, next) => {
    if (!to.meta.protected) {
        next();
        return;
    }
    if (to.name === 'MandatoryProfileUpdate'
        || to.name === 'RequiredDemographicsUpdateForm'
        || to.name === 'RedeemInvite'
        || to.name === 'InviteWithCode'
        || to.name === 'InitialProfileForm'
        || to.name === 'PendingCoiList'
        || to.name === 'coi'
    ) {
        next();
        return;
    }

    // Check to see if the user's profile is incomplete
    if (store.getters.currentUser.profileIncomplete) {
        router.replace({ name: 'InitialProfileForm', params: { redirectTo: to } });
        next();
        return;
    }

    // Check to see if the user has pending COIs
    if (store.getters.currentUser.hasPendingCois) {
        router.replace({ name: 'PendingCoiList', params: { redirectTo: to } });
        next();
        return;
    }

    // Check if the user needs to update credentials or expertise
    if (store.getters.currentUser.needsCredentials || store.getters.currentUser.needsExpertise) {
        router.replace({ name: 'MandatoryProfileUpdate', params: { redirectTo: to } });
        next();
        return;
    }

    // Check if the user needs to update profile demographics
    if (store.getters.currentUser.person.demographics_completed_date === null && !store.getters.currentUser.is_impersonating) {
        //  console.log('redirecting to demographics form');
        router.replace({ name: 'RequiredDemographicsUpdateForm', params: { redirectTo: to } });
        next();
        return;
    }

    next();

})

export default router
