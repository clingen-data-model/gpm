<script>
import LoginForm from '@/components/LoginForm.vue';
import { useAuthStore } from '@/stores/auth';

export default {
    name: "LoginPage",
    components: {
        LoginForm
    },
    setup() {
        return {
            authStore: useAuthStore(),
        }
    },
    computed: {
        isAuthed() {
            return this.authStore.isAuthed
        },
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
      <LoginForm @authenticated="redirect" />
    </card>
  </div>
</template>
