<template>
    <input-row 
        label="Step" 
        :modelValue="modelValue" 
        @input="$emit('update:modelValue', $event.target.value)"
        :errors="errors.step" 
        type="select" 
        :options="stepOptions"
    />
</template>
<script>
export default {
    props: {
        label: {
            required: false,
            type: String,
            default: 'Relevant Step'
        },
        modelValue: {
            required: true,
            // type: [Number, null]
        },
        errors: {
            type: Array,
            required: false,
            default: () => ([])   
        }
    },
    emits: [
        'update:modelValue'
    ],
    data() {
        return {
            stepOptions: [
                {value: 1, label: 'Definition'}, 
                {value: 2, label: 'Draft Specifications'}, 
                {value: 3, label: 'Pilot Specifications'}, 
                {value: 4, label: 'Sustained Curation'}, 
            ]
        }
    }
}
</script>