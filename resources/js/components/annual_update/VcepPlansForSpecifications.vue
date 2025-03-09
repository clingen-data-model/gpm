<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VcepPlansForSpecifications',
    components: {
        ApplicationSection,
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
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
    },
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at);
        }
    }
}
</script>

<template>
    <ApplicationSection title="Plans for rule specification of additional genes">
        <input-row
            v-model="workingCopy.data.specification_plans"
            :disabled="isComplete"
            :errors="errors.specification_plans"
            label="Are you planning to start the rule specification process for a new gene in the coming year?"
            type="radio-group"
            :options="[{value: 'yes', label: 'Yes'}, {value: 'no', label: 'No'}]"
            vertical
        />
        <input-row
            v-if="workingCopy.data.specification_plans == 'yes'"
            v-model="workingCopy.data.specification_plans_details"
            :disabled="isComplete" 
            :errors="errors.specification_plans_details" 
            type="large-text" 
            label="What are your plans?" 
            vertical
            class="ml-4"
        />
    </ApplicationSection>
</template>
