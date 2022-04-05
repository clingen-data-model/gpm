<template>
    <div class="border-t pt-3 mb-1 mt-3 flex space-x-3">
        <slot>
            <button class="btn" @click="cancel" v-if="showCancel" :disabled="$attrs.disabled">{{cancelText}}</button>
            <button class="btn" :class="submitVariant" @click="submit" :disabled="$attrs.disabled">{{submitText}}</button>
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
        },
        submitVariant: {
            type: String,
            default: 'blue'
        }
    },
    emits: [
        'cancelClicked',
        'submitClicked',
        'canceled',
        'submitted',
        'submit',
        'cancel',
    ],
    methods: {
        cancel () {
            this.$emit('cancelClicked');
            this.$emit('canceled');
            this.$emit('cancel');
        },
        submit () {
            this.$emit('submitClicked');
            this.$emit('submitted');
            this.$emit('submit');
        }
    }
}
</script>