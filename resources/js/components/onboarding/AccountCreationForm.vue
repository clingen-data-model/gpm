<script>
import {ref, onMounted} from 'vue'
import {redeemInvite, redeemInviteForExistingUser} from '@/domain/onboarding_service'
import isValidationError from '@/http/is_validation_error'
import { useStore } from 'vuex'
import LoginForm from '@/components/LoginForm.vue'
import CILogonProviderPicker from '@/components/auth/CILogonProviderPicker.vue'
import { cilogonEnabled } from '@/domain/cilogon'

export default {
    name: 'AccountCreationForm',
    components: {
        LoginForm,
        CILogonProviderPicker
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
        const email = ref(null);
        const password = ref(null);
        const password_confirmation = ref(null);

        const createAccount = async () => {
            try {
                await redeemInvite(props.invite, email.value, password.value, password_confirmation.value);
                await store.dispatch('login', {email: email.value, password: password.value})
                context.emit('saved')
            } catch (error) {
                if (isValidationError(error)) {
                    errors.value = error.response.data.errors;
                }
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

        const syncEmail = () => {
            email.value = props.invite.person.email
        }

        onMounted(() => syncEmail());

        return {
            cilogonEnabled,
            errors,
            email,
            password,
            password_confirmation,
            createAccount,
            redeemForExistingUser,
        }
    }
}
</script>
<template>
  <div>
    <pre />
    <div v-if="invite.person.user_id">
      <static-alert>
        It looks like you've already activated you account.  Please login to continue.
      </static-alert>
      <LoginForm :invite-code="invite.code" @authenticated="redeemForExistingUser" />
    </div>
    <div v-else>
      <p class="text-lg">
        Create your account
      </p>
      <div v-if="cilogonEnabled" class="my-4">
        <p class="mb-2">
          Continue with your ORCID, Google, Microsoft, GitHub, or institution account:
        </p>
        <CILogonProviderPicker :invite-code="invite.code" />
        <p class="text-sm text-gray-600 mt-2">
          Or create a password below.
        </p>
      </div>
      <input-row
        v-model="email" 
        label="Email" 
        :errors="errors.email" 
        label-width-class="w-24"
      />
      <input-row
        v-model="password" 
        label="Password" type="password" 
        :errors="errors.password" 
        label-width-class="w-24"
      />
      <input-row
        v-model="password_confirmation" 
        label="Confirm Password" type="password" 
        :errors="errors.password" 
        label-width-class="w-24"
      />
      <div class="flex flex-row-reverse">
        <button class="btn blue" @click="createAccount">
          Next
        </button>
      </div>
    </div>
  </div>
</template>