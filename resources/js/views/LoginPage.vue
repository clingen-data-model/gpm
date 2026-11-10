<script>
import LoginForm from '@/components/LoginForm.vue';
import { mapGetters } from 'vuex';
import { cilogonErrorMessage } from '@/domain/cilogon';

export default {
    name: "LoginPage",
    components: {
        LoginForm
    },
    computed: {
        ...mapGetters(['isAuthed']),
        cilogonError() {
            return cilogonErrorMessage(this.$route.query.cilogon_error);
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
