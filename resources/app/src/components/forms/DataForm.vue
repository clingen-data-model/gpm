<script>
import DataFormField from '@/components/forms/DataFormField'
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'DataForm',
    components: {
        DataFormField
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        },
        fields: {
            type: Array,
            required: true,
        },
    },
    emits: [
        ...mirror.emits
    ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>
<template>
    <div class="data-form">
        <div v-for="field in fields" :key="field.name">

            <data-form-field
                v-model="workingCopy"
                :field="field"
                :errors="errors" 
            />

        </div>
    </div>
</template>
