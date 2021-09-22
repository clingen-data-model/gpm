<template>
    <label>
        <slot name="input">
            <input type="checkbox" 
                :value="modelValue" 
                @change="$emit('change')" 
                @input="$emit('update:modelValue')"
            >
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
}
</script>