<template>
    <input-row :label="label" :errors="errors">
        <select :value="modelValue" @input="$emit('update:modelValue', $event.target.value)">
            <option :value="null">No specific step</option>
            <option v-for="step in steps" :key="step" :value="step">{{step}}</option>
        </select>
    </input-row>

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
    computed: {
        steps () {
            return [1,2,3,4];
        },
    }
}
</script>