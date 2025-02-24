<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VariantCurationWorkflow',
    components: {
        ApplicationSection
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
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
    },
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}

</script>
<template>
    <ApplicationSection title="Changes to plans for variant curation workflow">
        <input-row 
            :disabled="isComplete"
            v-model="workingCopy.data.variant_workflow_changes"
            type="radio-group"
            label="Has the Expert Panel made any changes to its workflow?"
            :errors="errors.variant_workflow_changes"
            :options="[{value: 'yes'},{value: 'no' }]"
            vertical
        ></input-row>
        <transition name="slide-fade-down">
            <input-row 
                :disabled="isComplete"
                v-show="workingCopy.data.variant_workflow_changes == 'yes'"
                v-model="workingCopy.data.variant_workflow_changes_details"
                type="large-text"
                label="Please explain"
                :errors="errors.variant_workflow_changes_details"
                vertical
                class="ml-4"
            />
        </transition>
    </ApplicationSection>
</template>