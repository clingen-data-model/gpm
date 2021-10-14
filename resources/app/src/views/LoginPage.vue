<template>
    <div>
        <card title="Login" class="md:w-1/2 mx-auto">
            <login-form @authenticated="redirect"></login-form>
        </card>
        <div class="mx-auto md:w-1/2 mt-8 p-4 bg-white border border-gray-300 rounded">
            <router-link class="block text-center btn blue btn-lg w-full" :to="{name: 'RedeemInvite'}">
                Redeem your invite
            </router-link>
        </div>
    </div>
</template>
<script>
import LoginForm from '@/components/LoginForm';
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
            let route = {name: 'home'};
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