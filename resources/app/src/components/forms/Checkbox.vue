<template>
    <label class="mb-2">
        <slot name="input">
            <input 
                type="checkbox" 
                v-model="val" 
                class="mt-1" 
                :value="value"
                :id="checkboxId"
                v-bind="$attrs"
            >
        </slot>
        <div>
            <slot>
                {{label}}
            </slot>
        </div>
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
        },
        value: {
            required: false,
            default: () => true
        }
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