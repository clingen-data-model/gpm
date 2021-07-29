<template>
    <card title="Login" class="lg:w-1/2 mx-auto">
        <login-form @authenticated="redirect"></login-form>
    </card>
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