import router from '.'
import store from '../store/index'
import { checkPermissionAndPersonOwnership } from './route_guards'

const PersonDetail = () => import (/* person-detail */ '@/components/people/PersonDetail.vue')
const PersonEdit = () => import (/* person-detail */ '@/views/PersonEdit.vue')
const PeopleList = () => import (/* people-index */ '@/views/PeopleList.vue')
const OnboardingWizard = () => import (/* onboarding-wizard */ '@/views/OnboardingWizard.vue')

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
]