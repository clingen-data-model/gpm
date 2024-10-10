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
        
        <p><router-link class="text-blue-500 underline" :to="{name: 'reset-password'}">Forget your password?</router-link></p>

        <!-- Updated button with wider styling -->
        <button-row>
            <button class="btn blue w-full max-w-md" @click="authenticate" name="login-button">Log in</button>
        </button-row>
    </form-container>
</template>



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