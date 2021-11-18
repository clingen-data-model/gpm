<template>
    <div class="flex space-x-2 mb-2">
        <slot name="input">
            <input type="checkbox" v-model="val" :id="checkboxId" class="mt-1">
        </slot>
        <label :for="checkboxId">
            <slot>
                {{label}}
            </slot>
        </label>
    </div>
</template>
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
    },
    emits: [
        'update:modelValue',
        'change'
    ],
    computed: {
        checkboxId () {
            return `checkbox-${this.uid}`;
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