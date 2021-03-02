<template>
    <div>
        <input-row v-model="email" label="Email" type="text" :errors="errors.email" required></input-row>
        <input-row v-model="password" label="Password" type="password" :errors="errors.password" required></input-row>
        
        <p><router-link class="text-blue-500 underline" :to="{name: 'reset-password'}">Forget your password?</router-link></p>

        <button-row><button class="btn blue" @click="authenticate">Log in</button></button-row>
    </div>
</template>
<script>
import is_validation_error from '../http/is_validation_error'

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
    computed: {

    },
    methods: {
        async authenticate() {
            try {
                await this.$store.dispatch('login', {email: this.email, password: this.password})
                await this.$store.dispatch('getCurrentUser');
                this.$router.push('/')
            } catch (e) {
                if (is_validation_error(e)) {
                    this.errors = e.response.data.errors
                }
            }
        }
    }
}
</script>