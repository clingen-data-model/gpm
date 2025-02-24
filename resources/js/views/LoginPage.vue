<template>
    <div>
        <card title="Login" class="md:w-1/2 mx-auto">
            <LoginForm @authenticated="redirect"></LoginForm>
        </card>
    </div>
</template>

<script>
import LoginForm from '@/components/LoginForm.vue';
import { mapGetters } from 'vuex';

export default {
    name: "LoginPage",
    components: {
        LoginForm
    },
    computed: {
        ...mapGetters(['isAuthed'])
    },
    watch: {
        isAuthed () {
            this.redirectIfAuthed();
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
    },
    mounted() {
        this.redirectIfAuthed();
    }
};
</script>
