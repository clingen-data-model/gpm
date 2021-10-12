<template>
    <div class="w-64 mx-auto">
        <label for="invite-code-input" class="text-lg block">
            Enter your registration code:
        </label>
        <input type="text" v-model="inviteCode" placeholder="XXXXXXXXXX" class="w-full">
        <button class="btn blue btn-lg block mt-2 w-full" @click="submitCode">Submit</button>
    </div>
</template>
<script>
export default {
    name: 'InviteRedemptionCode',
    props: {
    },
    data() {
        return {
            inviteCode: null
        }
    },
    computed: {

    },
    methods: {
        submitCode() {
            this.$emit('codeverified', this.inviteCode);
        }
    }
}
</script>