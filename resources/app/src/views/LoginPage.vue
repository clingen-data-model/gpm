<template>
    <div>
        <card title="Login" class="md:w-1/2 mx-auto">
            <login-form @authenticated="redirect"></login-form>
            <div class="mt-4 text-left">
                <router-link class="text-blue-500 underline" :to="{name: 'RedeemInvite'}">
                    Redeem your invite
                </router-link>
            </div>
        </card>
    </div>
</template>



<script>
import LoginForm from '@/components/LoginForm.vue';
import { mapGetters } from 'vuex'

export default {
    name: "LoginPage",
    components: {
        LoginForm
    },
    computed: {
        ...mapGetters(['isAuthed'])
    },
    watch: {
        isAuthed: function () {
            this.redirectIfAuthed();
        }
    },
    methods: {
        redirect() {
            let route = {name: 'Dashboard'};
            if (this.$route.query.redirect) {
                route = this.$route.query.redirect;
            }
            if (this.$route.redirectedFrom && this.$route.redirectedFrom.name !== 'login') {
                route = this.$route.redirectedFrom
            }
            this.$router.push(route)
        },
        redirectIfAuthed(){
            if (this.isAuthed) {
                this.redirect()
            }
        }
    },
    mounted() {
        this.redirectIfAuthed();
    }
}
</script>

<style scoped>
.router-link {
    color: #888; /* Even lighter gray color */
    font-size: 0.85em; /* Slightly smaller font size */
    text-decoration: none; /* No underline by default */
}

.router-link:hover {
    text-decoration: underline; /* Underline on hover */
}
</style>