<template>
    <form-container @keyup.enter="authenticate">
        <input-row 
            v-model="email" 
            label="Email" 
            type="text" 
            :errors="errors.email" 
            name="email" 
            required 
        />
        <input-row 
            v-model="password" 
            label="Password" 
            type="password" 
            :errors="errors.password" 
            name="password" 
            required 
        />

        <!-- Links Section aligned to the left under the input fields -->
        <div class="mt-2 max-w-xs">
            <p>
                <router-link class="text-blue-500 underline" :to="{name: 'reset-password'}">Forgot your password?</router-link>
            </p>
            <p>
                <router-link class="text-blue-500 underline" :to="{name: 'RedeemInvite'}">Redeem your invite</router-link>
            </p>
        </div>

        <!-- Left-align the Log In button -->
        <button-row class="mt-4 max-w-xs">
            <button class="btn blue w-auto px-4" @click="authenticate" name="login-button">Log in</button>
        </button-row>
    </form-container>
</template>

<style scoped>
/* Ensure that the links and button align properly */
.max-w-xs {
    max-width: 300px; /* Match the width of your input fields */
}

button-row {
    width: 100%; /* Ensures full width of container for alignment */
}
</style>

<script>
import is_validation_error from '@/http/is_validation_error'
import isAuthError from '@/http/is_auth_error'

export default {
    props: {
    },
    data() {
        return {
            email: null,
            password: null,
            errors: {}
        }
    },
    emits: [
        'authenticated',
        'authenticationFailed'
    ],
    computed: {

    },
    methods: {
        async authenticate() {
            try {
                await this.$store.dispatch('login', {email: this.email, password: this.password})
            } catch (e) {
                if (is_validation_error(e)) {
                    this.errors = e.response.data.errors
                    this.$emit('authenticationFailed')
                }
                throw e;
            }

            try {
                await this.$store.dispatch('getCurrentUser')
            } catch (e) {
                if (isAuthError(e)) {
                    this.$emit('authenticationFailed')
                }
                throw e
            }
            this.$emit('authenticated');

        }
    }
}
</script>