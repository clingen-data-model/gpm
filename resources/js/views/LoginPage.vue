<script>
import ClerkSignIn from '@/components/ClerkSignIn.vue';
import { mapGetters } from 'vuex';

export default {
    name: "LoginPage",
    components: {
        ClerkSignIn
    },
    computed: {
        ...mapGetters(['isAuthed']),
        redirectUrl() {
            if (typeof this.$route.query.redirect === 'string') {
                return this.$route.query.redirect;
            }
            return '/';
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
  <div class="flex justify-center mt-8">
    <ClerkSignIn :redirect-url="redirectUrl" />
  </div>
</template>
