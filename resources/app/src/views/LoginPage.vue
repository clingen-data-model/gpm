<template>
    <card title="Login" class="w-1/2 mx-auto">
        <login-form @authenticated="goHome"></login-form>
    </card>
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
        goHome() {
            console.log('going home');
            this.$router.push({name: 'home'})
        },
        redirectIfAuthed(){
            if (this.isAuthed) {
                this.$router.replace({name: 'home'});
            }
        }
    },
    mounted() {
        this.redirectIfAuthed();
    }
}
</script>