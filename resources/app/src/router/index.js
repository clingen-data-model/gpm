import { createRouter, createWebHistory } from 'vue-router'
import store from '../store/index'
import applicationRoutes from './applications'
import peopleRoutes from './people'
import authRoutes from './auth'
import cdwgRoutes from './cdwgs'
import groupRoutes from './groups'
import adminRoutes from './admin'
import userRoutes from './users'
import MemberForm from '@/components/groups/MemberForm.vue'
import DemographicsForm from '@/components/people/DemographicsFormOptionsFinal.vue';
import axios from 'axios';
const baseUrl = '/api/people';
let uuid = "3716f9f6-8f04-479e-af3e-5e6ce1f2abaa";
//mport Vue from 'vue';

//Vue.prototype.$isDemoFormComplete = false;

let isDemoFormComplete = false;
let routeDashboard = true;



async function getUser(uuid) {
    let formData = null;
    let error = null;
  
    try {
      const response = await axios.get(`${baseUrl}/${uuid}`);
      formData = response.data;
      console.log(formData);
    //  console.log(formData?.demo_form_complete); // Check the actual value
//console.log(typeof formData?.demo_form_complete); // Check the data type

      return { formData }; // Return formData as part of an object
    } catch (err) {
      console.error("Failed to fetch user:", err);
      error = err;
      return { error }; // Return error as part of an object
    }
  }
  

//getUser(uuid)
//.then(result => {
    
 //   const isDemoFormComplete = Boolean(result.formData?.data.demo_form_complete);
  //  if (!isDemoFormComplete) {
  //      routeDashboard = false;
  //      console.log("Demo form is not complete.");
  //    }
   // else{
   //     routeDashboard = true;
   //     console.log("Dashboard before routing check:", routeDashboard);
   // }
      
   // if (!result.formData?.demo_form_complete !== 1) {
    //  console.log("Demo form is not complete.");
    //}
 // });

const routes = [
    { name: 'Dashboard',
        path: '/',
       component: () => import (/* webpackChunkName "dashboard" */ '@/views/Dashboard.vue'),
     
       //TODO - to enable routing back to the Dashboard from Demographics form, needed to comment out this logic.  Need to analyze what is neede for Demographics. 
        meta: {
          protected: true
       }
    },

    //to do enhance conditional logic to route to form if incomplete
   // {   
    //    name: 'DemographicsForm',
    //    path: '/demographics/:uuid',
   //     redirect: '/demographics/:uuid',
   //     props: true,
   //     component: DemographicsForm
       // component: DemographicsForm
   //  component: () =>
    // import ('@/components/people/DemographicsFormOptionsFinal.vue'),
    //   },

    {
        name: 'ApplicationSummary',
        path: '/application-summary',
        redirect: '/application-summary/vceps',
        component: () => import (/* webpackChunkName "applications-summary" */ '@/views/ApplicationSummary.vue'),
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
    {
        name: 'AnnualUpdatesList',
        path: '/annual-updates',
        component: () => import (/* webpackChunkName "annual-updates" */ '@/views/AnnualUpdatesList.vue'),
        meta: {
            protected: true,
            permissions: ['annual-updates-manage']
        },
    },
    {
        name: 'AnnualUpdateDetail',
        path: '/annual-updates/:id',
        component: () => import (/* webpackChunkName "annual-updates" */ '@/views/AnnualUpdateDetail.vue'),
        props: true,
        meta: {
            protected: true,
        },
        children: [
            {
                name: 'AnnualUpdateAddMember',
                path: 'members/add',
                component: MemberForm,
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
                component: MemberForm,
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
    {
        name: 'CoiNoCode',
        path: '/coi',
        redirect: '/'
    },
    {
        name: 'coi',
        path: '/coi/:code',
        component: () => import (/* webpackChunkName "coi-survey" */ '@/views/Coi.vue'),
        props: true,
        meta: {
            protected: true
        }
    },
    {
        name: 'alt-coi',
        path: '/expert-panels/:name/coi/:code',
        component: () => import (/* webpackChunkName "coi-survey" */ '@/views/Coi.vue'),
        props: true,
        meta: {
            protected: true
        }
    },
    { name: 'about',
        path: '/about',
        component: () =>
            import ( /* webpackChunkName: "about" */ '@/views/About.vue')
    },
    { name: 'PendingCoiList',
        path: '/onboarding/cois',
        component: () => import( /* webpackChunkName "coi-survey" */ '@/views/PendingCoiList.vue'),
        props: true,
        meta: {
            protected: true
        }

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
    {
        name: 'reports',
        path: '/reports',
        component: () => import (/* webpackChunkName "reports-index" */ '@/views/Reports.vue'),
        meta: {
            protected: true,
            permissions: ['reports-pull']
        },
        // beforeEnter: (to) => hasPermission(to, 'reports-pull')
    },
]

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
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

    try{
        await store.dispatch('checkAuth')
        await store.dispatch('getCurrentUser');
    } catch (error) {
        console.log(error);
    }

    if (!to.name.includes('login') && !store.getters.isAuthed) {
        next({name: 'login', query: { redirect: to.fullPath }});
        return;
    }

    if (to.name == 'login' && store.getters.isAuthed) {
        next({name: 'Dashboard'})
        return;
    }

    if (to.meta.permissions && Array.isArray(to.meta.permissions) && to.meta.permissions.length > 0) {
        if (store.getters.currentUser.hasAnyPermission(to.meta.permissions)) {
            next();
            return;
        }
        router.replace({name: 'Dashboard'})
        store.commit('pushError', 'You don\'t have permission to access '+to.path)
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
    //if (to.name == 'DemographicsForm'

     if   (to.name == 'MandatoryProfileUpdate'
        || to.name == 'RequiredDemographicsUpdateForm'
        || to.name == 'RedeemInvite'
        || to.name == 'InviteWithCode'
        || to.name == 'InitialProfileForm'
        || to.name == 'PendingCoiList'
        || to.name == 'coi'
    ) {
        next();
        return;
    }

    //create if logic to route for incomplete Demographics Form
    //router.replace({name: 'DemographicsForm', params: {redirectTo: to}});

    // Check to see if the user's profile is incomplete
    if (store.getters.currentUser.profileIncomplete) {
        router.replace({name: 'InitialProfileForm', params: {redirectTo: to}});
        next();
        return;
    }

    // Check to see if the user has pending COIs
    if (store.getters.currentUser.hasPendingCois) {
        router.replace({name: 'PendingCoiList', params: {redirectTo: to}});
        next();
        return;
    }

    // Check if the user needs to update credentials or expertise
    if (store.getters.currentUser.needsCredentials || store.getters.currentUser.needsExpertise) {
        router.replace({name: 'MandatoryProfileUpdate', params: {redirectTo: to}});
        next();
        return;
    }

    getUser(uuid)
.then(result => {
    
    const isDemoFormComplete = Boolean(result.formData?.data.demo_form_complete);
    if (!isDemoFormComplete) {
        routeDashboard = false;
        console.log("Demo form is not complete.");
      }
    else{
        routeDashboard = true;
        console.log("Dashboard before routing check:", routeDashboard);
    }
      
   // if (!result.formData?.demo_form_complete !== 1) {
    //  console.log("Demo form is not complete.");
    //}
  });

    // Check if user needs to update required demographics
   
    // TODO: set criteria more clearly, probably check for whether demographics response have ever been stored
    //console.log(isDemoFormComplete)

   // console.log("Dashboard before routing check:", Dashboard);
    if (!routeDashboard) {
   // if (!store.getters.currentUser.hasRequiredDemographics) {
        router.replace({name: 'RequiredDemographicsUpdateForm', params: {redirectTo: to}});
        next();
        //store.getters.currentUser.hasRequiredDemographics
        return;
    }

    next();

})

//import axios from 'axios';

//const baseUrl = 'https://example.com/api/users'; // Ensure baseUrl is defined




export default router


