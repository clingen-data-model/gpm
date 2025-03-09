<script>
export default {
    name: 'Checkbox',
    props: {
        modelValue: {
            required: false,
            default: false
        },
        label: {
            required: false
        },
        value: {
            required: true,
        }
    },
    emits: [
        'update:modelValue',
        'change'
    ],
    computed: {
        radioId () {
            return `radiobutton-${this.uid}`;
        },
        val: {
            get () {
                return this.modelValue
            },
            set (val) {
                this.$emit('update:modelValue', val)
            }
        }
    }
}
</script>
<template>
  <label class="mb-2">
    <slot name="input">
      <input 
        :id="radioId"
        v-model="val" 
        type="radio" 
        class="mt-1" 
        :disabled="$attrs.disabled" 
        :value="value"
      >
    </slot>
    <div>
      <slot>
        {{ label }}
      </slot>
    </div>
  </label>
</template>