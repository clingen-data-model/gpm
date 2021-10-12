<template>
    <div>
        <div class="flex items-center">
                <button v-if="hasRole('super-user')" class="btn btn-xl" @click="goBack">&lt;</button>
                <div class="onboarding-container" :style="`width: ${currentStepWidth}`">
                    <keep-alive>
                        <transition :name="animationDirection" mode="out-in">        
                            <component 
                                :is="currentStepComponent" 
                                v-bind:invite="invite" 
                                v-bind:person="invite.person"
                                ref="stepForm"
                                @codeverified="handleCodeVerified"
                                @ok="goForward"
                                @saved="goForward"
                                @back="goBack"
                            ></component>
                        </transition>
                    </keep-alive>
                </div>
                <button v-if="hasRole('super-user')" @click="goForward" class="btn btn-xl">&gt;</button>
        </div>
    </div>
</template>
<script>
import OnboardingSteps from '@/components/onboarding/OnboardingSteps'
import InviteRedemptionForm from '@/components/onboarding/InviteRedemptionForm'
import AccountCreationForm from '@/components/onboarding/AccountCreationForm'
import ProfileForm from '@/components/people/ProfileForm'
import DemographicsForm from '@/components/people/DemographicsForm'
import Person from '@/domain/person'

const stepComponents = [
    InviteRedemptionForm,
    OnboardingSteps,
    AccountCreationForm,
    ProfileForm,
    DemographicsForm,
];

const stepWidths = [
    '20rem',
    '20rem',
    '20rem',
    '40rem',
    '40rem'
];

export default {
    name: 'OverviewWizard',
    components: {
        InviteRedemptionForm,
        OnboardingSteps,
        AccountCreationForm,
        ProfileForm,
        DemographicsForm,
    },
    props: {
        
    },
    data() {
        return {
            animationDirection: 'forward',
            currentStepIdx: 3,
            invite: {
                inviteCode: 'blahblahblah',
                // inviter: {
                //     name: 'Beans',
                //     type: 'VCEP'
                // },
                person: new Person({
                    first_name: 'tj',
                    last_name: 'ward',
                    email: 'beans@ithrewup.com'
                })
            },
        }
    },
    computed: {
        currentStepComponent () {
            return stepComponents[this.currentStepIdx]
        },
        currentStepWidth () {
            return stepWidths[this.currentStepIdx]
        }
    },
    methods: {
        handleCodeVerified () {
            console.log('handleCodeVerified');
            this.goForward();
        },
        submitCode() {

        },
        goBack () {
            this.animationDirection = 'fade';
            if (this.currentStepIdx == 0) {
                this.currentStepIdx = stepComponents.length-1;
                return;
            }
            this.currentStepIdx -= 1;
        },
        goForward() {
            this.animationDirection = 'fade';
            if (this.currentStepIdx == stepComponents.length-1) {
                this.currentStepIdx = 0;
                return;
            }
            this.currentStepIdx += 1;
        },
    }
}
</script>
<style lang="postcss" scoped>
    .onboarding-container {
        @apply shadow-lg border px-4 py-8 mx-auto mt-12 border-gray-300;
        transition: all .3s;
        overflow: hidden;
    }
    .forwards-enter-active,
    .forwards-leave-active,
    .backwards-enter-active,
    .backwards-leave-active {
        transition: all 0.3s ease-out;
    }
    
    .forwards-enter-from{
        transform: translateX(20rem);
        /* opacity: 0; */
    }
    .forwards-leave-to
    {
        transform: translateX(-20rem);
        /* opacity: 0; */
    }

    .backwards-enter-from {
        transform: translateX(-40rem)
        /* opacity: 0; */
    }
    .backwards-leave-to {
        transform: translateX(40rem)
        /* opacity: 0; */
    }
</style>