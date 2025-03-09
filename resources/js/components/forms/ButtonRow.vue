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
        },
        size: {
            type: String,
            default: 'md'
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
<template>
  <div class="border-t pt-3 mb-1 mt-3 flex space-x-3">
    <slot>
      <button
        v-if="showCancel"
        class="btn"
        :class="[`btn-${size}`]"
        :disabled="$attrs.disabled"
        @click="cancel"
      >
        {{ cancelText }}
      </button>
      <button
        class="btn"
        :class="[submitVariant, `btn-${size}`]"
        :disabled="$attrs.disabled"
        @click="submit"
      >
        {{ submitText }}
      </button>
      <slot name="extra-buttons" />
    </slot>
  </div>
</template>
