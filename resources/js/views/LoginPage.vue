<script>
import LoginForm from '@/components/LoginForm.vue';
import { mapGetters } from 'vuex';

export default {
    name: "LoginPage",
    components: {
        LoginForm
    },
    computed: {
        ...mapGetters(['isAuthed']),
        cilogonError() {
            const messages = {
                no_account: 'No GPM account is linked to that login. If you were invited, use the link in your invitation email; otherwise contact your group coordinator.',
                already_linked: 'That login is already linked to a different GPM account.',
                account_conflict: 'Another GPM account already uses this email address. Please contact support.',
                invite_invalid: 'That invitation is no longer valid. Please log in or contact your coordinator.',
                not_signed_in: 'Please log in before linking another login method.',
                cilogon_failed: 'Sign-in with CILogon did not complete. Please try again.',
            };
            const code = this.$route.query.cilogon_error;
            return code ? (messages[code] || messages.cilogon_failed) : null;
        }
    },
    watch: {
        isAuthed () {
            this.redirectIfAuthed();
        }
    },
    mounted() {
        this.redirectIfAuthed();
    },
    methods: {
        redirect() {
            let route = { name: 'Dashboard' };
            if (this.$route.query.redirect) {
                route = this.$route.query.redirect;
            }
            if (this.$route.redirectedFrom && this.$route.redirectedFrom.name !== 'login') {
                route = this.$route.redirectedFrom;
            }
            this.$router.push(route);
        },
        redirectIfAuthed() {
            if (this.isAuthed) {
                this.redirect();
            }
        }
    }
};
</script>

<template>
  <div>
    <card title="Login" class="md:w-1/2 mx-auto">
      <static-alert v-if="cilogonError" variant="danger" class="mb-4">
        {{ cilogonError }}
      </static-alert>
      <LoginForm @authenticated="redirect" />
    </card>
  </div>
</template>
