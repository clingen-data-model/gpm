<template>
    <card title="Login" class="w-1/2 mx-auto">
        <login-form></login-form>
    </card>
</template>
<script>
import LoginForm from '../components/LoginForm';

export default {
    name: "LoginPage",
    components: {
        LoginForm
    },
}
</script>