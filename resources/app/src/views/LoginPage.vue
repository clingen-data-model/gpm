<template>
    <card title="Login" class="lg:w-1/2 mx-auto">
        <login-form @authenticated="redirect"></login-form>
    </card>
    <!-- <pre>{{$route.path}}</pre> -->
</template>
<script>
import LoginForm from '../components/LoginForm';
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
            let routeName = 'home';
            if (this.$route.query && this.$route.query.redirect) {
                routeName = this.$route.query.redirect
            }
            this.$router.push({name: routeName})
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