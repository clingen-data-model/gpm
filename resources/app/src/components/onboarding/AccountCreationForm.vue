<template>
    <div>
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
            :errors="errors.password_confirmation" 
            label-width-class="w-24"
        ></input-row>
        <div class="flex flex-row-reverse">
            <button class="btn blue" @click="createAccount">Next</button>
        </div>
    </div>
</template>
<script>
import {ref, onMounted} from 'vue'
import {redeemInvite} from '@/domain/onboarding_service'
import isValidationError from '@/http/is_validation_error'
import { useStore } from 'vuex'

export default {
    name: 'AccountCreationForm',
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
                if (isValidationError(error )) {
                    errors.value = error.response.data;
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
        }
    }
}
</script>