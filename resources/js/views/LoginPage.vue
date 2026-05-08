<script>
import { SignIn, useAuth } from '@clerk/vue';
import LoginForm from '@/components/LoginForm.vue';
import { mapGetters } from 'vuex';
import { watch } from 'vue';

const CLERK_ENABLED = !!import.meta.env.VITE_CLERK_PUBLISHABLE_KEY

export default {
    name: "LoginPage",
    components: {
        LoginForm,
        SignIn,
    },
    setup() {
        return { clerkEnabled: CLERK_ENABLED }
    },
    computed: {
        ...mapGetters(['isAuthed'])
    },
    watch: {
        isAuthed () {
            this.redirectIfAuthed();
        }
    },
    mounted() {
        this.redirectIfAuthed();

        // When Clerk is active, watch for sign-in completion and redirect.
        if (CLERK_ENABLED) {
            const { isSignedIn, isLoaded } = useAuth()
            watch([isSignedIn, isLoaded], ([signedIn, loaded]) => {
                if (loaded && signedIn) {
                    this.$store.dispatch('getCurrentUser', true).then(() => {
                        this.redirect()
                    })
                }
            })
        }
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
    <template v-if="clerkEnabled">
      <SignIn class="mx-auto" />
    </template>
    <template v-else>
      <card title="Login" class="md:w-1/2 mx-auto">
        <LoginForm @authenticated="redirect" />
      </card>
    </template>
  </div>
</template>
