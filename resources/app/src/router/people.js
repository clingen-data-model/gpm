import router from '.'
import store from '../store/index'

const PersonDetail = () => import (/* person-detail */ '@/components/people/PersonDetail')
const PersonForm = () => import (/* person-detail */ '@/components/people/PersonForm')
const PeopleIndex = () => import (/* people-index */ '@/views/PeopleIndex')
const OnboardingWizard = () => import (/* onboarding-wizard */ '@/views/OnboardingWizard')

const checkPermissionAndPersonOwnership = async (to, from) => {
    if (store.getters.currentUser.hasPermission('people-manage')) {
        return true;
    }
    if (!store.getters['people/currentItem'] || store.getters['people/currentItem'].uuid != to.params.uuid) {
        await store.dispatch('people/getPerson', {uuid: to.params.uuid})
    }
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
            { name: 'person-edit',
                path: 'edit',
                components: {
                    default: PersonDetail,
                    modal: PersonForm
                },
                meta: {
                    showModal: true,
                    protected: true
                },
                props: true,
                beforeEnter: [checkPermissionAndPersonOwnership]
            }
        ]
    },
    { name: 'RedeemInvite',
        path: '/redeem-invite',
        component: OnboardingWizard,
        beforeEnter: () => {
            if (store.getters.currentUser) {
                router.replace({name: 'Dashboard'});
            }
            return true;
        }
    }
]