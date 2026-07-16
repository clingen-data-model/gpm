<script>
import {ref, computed} from 'vue'
import {redeemInvite, redeemInviteForExistingUser} from '@/domain/onboarding_service'
import isValidationError from '@/http/is_validation_error'
import { useStore } from 'vuex'
import LoginForm from '@/components/LoginForm.vue'
import ClerkSignUp from '@/components/ClerkSignUp.vue'

export default {
    name: 'AccountCreationForm',
    components: {
        LoginForm,
        ClerkSignUp
    },
    props: {
        invite: {
            type: Object,
            required: true
        }
    },
    emits: [
      'saved',
    ],
    setup (props, context) {
        const store = useStore();
        const errors = ref({});
        const submitting = ref(false);

        // After Clerk sign-up the user is authenticated with Clerk; redeeming
        // the invite links that identity to the invited person (creating the
        // local account if needed) and establishes the GPM session.
        const onSignedUp = async () => {
            if (submitting.value) {
                return;
            }
            submitting.value = true;
            try {
                await redeemInvite(props.invite);
                await store.dispatch('forceGetCurrentUser');
                context.emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    errors.value = error.response.data.errors;
                }
            } finally {
                submitting.value = false;
            }
        }

        const redeemForExistingUser = async () => {
            try {
                await redeemInviteForExistingUser(props.invite);
                context.emit('saved')
            } catch (error) {
                if (isValidationError(error)) {
                    errors.value = error.response.data
                }
            }
        }

        const alreadyHasAccount = computed(() => !!props.invite.person.user_id);

        return {
            errors,
            email: props.invite.person.email,
            alreadyHasAccount,
            onSignedUp,
            redeemForExistingUser,
        }
    }
}
</script>
<template>
  <div>
    <div v-if="alreadyHasAccount">
      <static-alert>
        It looks like you've already activated your account. Please login to continue.
      </static-alert>
      <LoginForm @authenticated="redeemForExistingUser" />
    </div>
    <div v-else>
      <p class="text-lg">
        Create your account
      </p>
      <ClerkSignUp :email="email" @signed-up="onSignedUp" />
    </div>
  </div>
</template>
