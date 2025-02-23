<template>
    <div>
        <pre></pre>
        <div v-if="invite.person.user_id">
            <static-alert>
                It looks like you've already activated you account.  Please login to continue.
            </static-alert>
            <login-form @authenticated="redeemForExistingUser"></login-form>
        </div>
        <div v-else>
            <p class="text-lg">Create your account</p>
            <input-row label="Email" 
                v-model="email" 
                :errors="errors.email" 
                label-width-class="w-24"
            ></input-row>
            <input-row label="Password" 
                v-model="password" type="password" 
                :errors="errors.password" 
                label-width-class="w-24"
            ></input-row>
            <input-row label="Confirm Password" 
                v-model="password_confirmation" type="password" 
                :errors="errors.password" 
                label-width-class="w-24"
            ></input-row>
            <div class="flex flex-row-reverse">
                <button class="btn blue" @click="createAccount">Next</button>
            </div>
        </div>
    </div>
</template>
<script>
import LoginForm from '@/components/LoginForm.vue'
import {redeemInvite, redeemInviteForExistingUser} from '@/domain/onboarding_service'
import isValidationError from '@/http/is_validation_error'
import {onMounted, ref} from 'vue'
import { useStore } from 'vuex'

export default {
    name: 'AccountCreationForm',
    components: {
        LoginForm
    },
    props: {
        invite: {
            type: Object,
            required: true
        }
    },
    setup (props, context) {
        const store = useStore();
        let errors = ref({});
        let email = ref(null);
        let password = ref(null);
        let password_confirmation = ref(null);

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