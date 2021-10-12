<template>
    <div>
        <p class="text-lg">Create your account</p>
        <input-row label="Email" 
            v-model="email" 
            :errors="errors.email" 
            :label-width="24"
        ></input-row>
        <input-row label="Password" 
            v-model="password" type="password" 
            :errors="errors.password" 
            :label-width="24"
        ></input-row>
        <input-row label="Confirm Password" 
            v-model="confirm_password" type="password" 
            :errors="errors.confirm_password" 
            :label-width="24"
        ></input-row>
        <div class="flex flex-row-reverse">
            <button class="btn blue" @click="createAccount">Next</button>
        </div>
    </div>
</template>
<script>
export default {
    name: 'AccountCreationForm',
    props: {
        invite: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            errors: {},
            email: null,
            password: null,
            confirmPassword: null
        }
    },
    computed: {

    },
    methods: {
        createAccount () {
            this.$emit('saved')
        }
    }
}
</script>