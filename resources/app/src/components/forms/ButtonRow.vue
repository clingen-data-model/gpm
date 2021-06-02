<template>
    <div class="border-t pt-3 mb-1 mt-3 flex space-x-3">
        <slot>
            <button class="btn" @click="cancel" v-if="showCancel">{{cancelText}}</button>
            <button class="btn blue" @click="submit">{{submitText}}</button>
        </slot>
    </div>
</template>
<script>
export default {
    name: "ButtonRow",
    props: {
        showCancel: {
            type: Boolean,
            default: true
        },
        cancelText: {
            type: String,
            default: 'Cancel'
        },
        submitText: {
            type: String,
            default: 'Submit'
        }
    },
    emits: [
        'cancelClicked',
        'submitClicked',
        'canceled',
        'submitted'
    ],
    methods: {
        cancel () {
            this.$emit('cancelClicked');
            this.$emit('canceled');
        },
        submit () {
            this.$emit('submitClicked');
            this.$emit('submitted');
        }
    }
}
</script>