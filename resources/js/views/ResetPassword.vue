<script>
import api from '../http/api'
import is_validation_error from '../http/is_validation_error'
export default {
    props: {

    },
    data() {
        return {
            email: null,
            errors: {},
            password: null,
            password_confirmation: null,
            successMessage: null
        }
    },
    computed: {
        hasToken () {
            return Boolean(this.$route.query.token)
        },
        submitText () {
            return this.hasToken ? 'Reset Password' : 'Send Password Reset Link';
        }
    },
    methods: {
        getResetLink() {
            api.post('/api/send-reset-password-link', {email: this.email})
                .then(response => {
                    this.successMessage = response.data.status;
                })
                .catch(error => {
                    if (is_validation_error(error)) {
                        this.errors = error.response.data.errors
                    }
                });
        },
        submitNewPassword () {
            const data = {
                token: this.$route.query.token,
                email: this.email,
                password: this.password,
                password_confirmation: this.password_confirmation,
            };

            api.post('/api/reset-password', data)
                .then(() => {
                    this.$store.dispatch('login', {email: this.email, password: this.password})
                        .then(() => {
                            this.$store.dispatch('getCurrentUser');
                            this.$router.replace('/');
                        })
                })
                .catch(error => {
                    if (is_validation_error(error)) {
                        this.errors = error.response.data.errors
                    }
                })
        },
        submitReset() {
            if (this.hasToken) {
                this.submitNewPassword()
                return;
            }

            this.getResetLink();
        }
    }
}
</script>
<template>
  <card title="Reset Your Password" class="w-2/3 mx-auto">
    <div v-if="successMessage" class="p-2 rounded border border-green-300 bg-green-100 text-green-700">
      {{ successMessage }}
    </div>
    <div v-else>
      <input-row v-model="email" type="text" label="Email" :errors="errors.email" />
      <input-row v-if="hasToken" v-model="password" type="password" label="New password" :errors="errors.password" />
      <input-row v-if="hasToken" v-model="password_confirmation" type="password" label="Confirm password" :errors="errors.password_confirmation" />
      <button-row :show-cancel="false" :submit-text="submitText" @submit-clicked="submitReset" />
    </div>
  </card>
</template>
