<template>
    <div>
        <div class="flex justify-center">
                <div class="onboarding-container" :style="`width: ${currentStepWidth}`">
                    <keep-alive>
                        <transition :name="animationDirection" mode="out-in">        
                            <component 
                                :is="currentStepComponent" 
                                v-bind:invite="invite" 
                                v-bind:person="invite.person"
                                v-bind:code="invite.coi_code"
                                ref="stepForm"
                                @codeverified="handleCodeVerified"
                                @ok="goForward"
                                @saved="goForward"
                                @back="goBack"
                            ></component>
                        </transition>
                    </keep-alive>
                </div>
                <p>
                    <router-link class="block link pt-2" :to="{name: 'login'}" v-if="!this.$store.getters.isAuthed">&lt; Log In</router-link>
                </p>
            </div>
    </div>
</template>
<script>
import OnboardingSteps from '@/components/onboarding/OnboardingSteps'
import InviteRedemptionForm from '@/components/onboarding/InviteRedemptionForm'
import AccountCreationForm from '@/components/onboarding/AccountCreationForm'
import ProfileForm from '@/components/onboarding/ProfileForm'
import Person from '@/domain/person'
import Coi from '@/views/Coi'

const stepComponents = [
    InviteRedemptionForm,
    OnboardingSteps,
    AccountCreationForm,
    ProfileForm,
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
    },
    props: {
        code: {
            required: false,
        }
    },
    data() {
        return {
            animationDirection: 'forward',
            currentStepIdx: 0,
            invite: {
                person: new Person({})
            },
        }
    },
    computed: {
        currentStepComponent () {
            return stepComponents[this.currentStepIdx]
        },
        currentStepWidth () {
            return stepWidths[this.currentStepIdx]
        },
        canContinue () {
            return !this.$store.getters.isAuthed
                || (
                    this.$store.getters.isAuthed && 
                    !this.$store.getters.currentUser.person.timezone
                );
        }

    },
    watch: {
        invite: {
            deep: true,
            handler: function (to) {
                if (to.inviter) {
                    stepComponents.push(Coi)
                }
            }
        },
        code: {
            immediate: true,
            handler: function (to) {
                this.invite.code = to
            }
        },
    },
    methods: {
        handleCodeVerified (invite) {
            this.invite = invite;
            this.goForward();
        },
        goBack () {
            this.animationDirection = 'fade';
            if (this.currentStepIdx == 0) {
                return;
            }
            this.currentStepIdx -= 1;
        },
        goForward() {
            this.animationDirection = 'fade';
            if (this.currentStepIdx == stepComponents.length-1) {
                this.$router.push({name: 'Dashboard'})
                return;
            }
            this.currentStepIdx += 1;
        },
        selectStep() {
            if (!this.invite.id) {
                this.currentStepIdx = 0;
                if (this.$route.query.code) {
                    this.invite.code = this.$route.query.code;
                }
                return;
            }
            if (this.invite.id) {
                if (this.$store.state.user.id) {
                    this.currentStepIndex = 3
                    return
                }
            }
        }
    }
}
</script>
<style lang="postcss" scoped>
    .onboarding-container {
        @apply shadow-lg border px-4 py-8 mx-auto mt-12 border-gray-300;
        transition: all .3s;
        overflow: hidden;
    }
    .dev-nav-button {
        @apply mt-12 p-2 bg-pink-100 mb-8 border border-pink-300;
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