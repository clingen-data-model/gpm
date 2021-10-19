<template>
    <label>
        <slot name="input">
            <input type="checkbox" v-model="val">
        </slot>
        &nbsp;
        <slot>
            {{label}}
        </slot>
    </label>
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
        }
    },
    emits: [
        'update:modelValue',
        'change'
    ],
    computed: {
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