<template>
    <card title="Login" class="w-1/2 mx-auto">
        <login-form></login-form>
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
        redirectIfAuthed(){
            if (this.isAuthed) {
                this.$router.replace({name: 'vceps'});
            }
        }
    },
    mounted() {
        this.redirectIfAuthed();
    }
}
</script>