<template>
    <application-section title="Changes to plans for variant curation workflow">
        <input-row 
            v-model="workingCopy.variant_workflow_changes"
            type="radio-group"
            label="Has the Expert Panel has made any changes to its workflow?"
            :errors="errors.variant_workflow_changes"
            :options="[{value: 'yes'},{value: 'no' }]"
            vertical
        ></input-row>
        <transition name="slide-fade-down">
            <input-row 
                v-show="workingCopy.variant_workflow_changes == 'yes'"
                v-model="workingCopy.variant_workflow_changes_details"
                type="large-text"
                label="Please explain"
                :errors="errors.variant_workflow_changes_details"
                vertical
                class="ml-4"
            />
        </transition>
    </application-section>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'

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
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}

</script>