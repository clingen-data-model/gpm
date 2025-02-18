import router from '.'
import store from '../store/index'
import { checkPermissionAndPersonOwnership } from './route_guards'

const PersonDetail = () => import ('@/components/people/PersonDetail.vue')
const PersonEdit = () => import ('@/views/PersonEdit.vue')
const PeopleList = () => import ('@/views/PeopleList.vue')
const OnboardingWizard = () => import ('@/views/OnboardingWizard.vue')
const RequiredProfileUpdate = () => import ('@/views/RequiredProfileUpdate.vue')

export default [
    { name: 'people-index',
            path: '/people',
            component: PeopleList,
            meta: {
                protected: true
            },
    },
    { name: 'PersonDetail',
        path: '/people/:uuid',
        component: PersonDetail,
        props: true,
        meta: {
            protected: true
        },
        children: [
            { name: 'PersonEdit',
                path: 'edit',
                components: {
                    default: PersonDetail,
                    modal: PersonEdit
                },
                meta: {
                    showModal: true,
                    protected: true,
                    title: 'Update Information'
                },
                props: true,
                beforeEnter: [checkPermissionAndPersonOwnership]
            }
        ]
    },
    {
        name: 'InviteWithCode',
        path: '/invites/:code',
        component: OnboardingWizard,
        beforeEnter: () => {
            if (store.getters.currentUser.id !== null) {
                router.replace({name: 'Dashboard'});
            }
            return true;
        },
        meta: {
            protected: false
        },
        props: true
    },
    { name: 'RedeemInvite',
        path: '/invites',
        component: OnboardingWizard,
        beforeEnter: () => {
            if (store.getters.currentUser.id !== null) {
                router.replace({name: 'Dashboard'});
            }
            return true;
        },
        meta: {
            protected: false
        }
    },
    { name: 'InitialProfileForm',
        path: '/onboarding/profile',
        component: () => import ('@/views/OnboardingProfileForm.vue')
    },

    {
        name: 'RequiredDemographicsUpdateForm',
        path: '/required-demographics-update',
        component: () => import ('@/views/RequiredDemographicsUpdateForm.vue'),
        meta: {
            protected: true
        },
    },

    {
        name: 'DemographicsForm',
        path: '/demographics/:uuid',
        props: true,
        component: () =>
            import ('@/components/people/DemographicsForm.vue'),
        meta: {
            protected: true
        },
    },


    {
        name: 'MandatoryProfileUpdate',
        path: '/required-profile-update',
        component: RequiredProfileUpdate,
        meta: {
            protected: true
        }
    },
]
