import router from '.'
import store from '../store/index'

const PersonDetail = () => import (/* person-detail */ '@/components/people/PersonDetail')
const PersonEdit = () => import (/* person-detail */ '@/views/PersonEdit')
// const PersonForm = () => import (/* person-detail */ '@/components/people/PersonForm')
const PeopleIndex = () => import (/* people-index */ '@/views/PeopleIndex')
const OnboardingWizard = () => import (/* onboarding-wizard */ '@/views/OnboardingWizard')

const checkPermissionAndPersonOwnership = async (to) => {
    // Check for system permission
    if (store.getters.currentUser.hasPermission('people-manage')) {
        return true;
    }

    // if we don't have a currentItem in people store, get the person by the uuid
    if (!store.getters['people/currentItem'] || store.getters['people/currentItem'].uuid != to.params.uuid) {
        await store.dispatch('people/getPerson', {uuid: to.params.uuid})
    }

    // check that user is the same user associated with the person
    if (store.getters['people/currentItem'].user_id == store.getters.currentUser.id) {
        return true;
    }

    store.commit('pushError', 'You don\'t have permission to edit this person\'s information');
    return false;
};

export default [
    { name: 'people-index',
            path: '/people',
            component: PeopleIndex,
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