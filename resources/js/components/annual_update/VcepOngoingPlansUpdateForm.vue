<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm.vue'
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VcepOngoingPlansUpdateForm',
    components: {
        VcepOngoingPlansForm,
        ApplicationSection
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits, 'updated'],
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
    <ApplicationSection title="Sustained Variant Curation">
        <VcepOngoingPlansForm 
            v-model="workingCopy"
            :errors="errors"
            :readonly="isComplete"
            @update="$emit('updated')"
        />
        <input-row v-model="workingCopy.data.ongoing_plans_updated" 
            vertical
            label="Does this current review method represent a change from previous years?"
            :errors="errors.ongoing_plans_updated"
            type="radio-group"
            :options="[{value:'yes'},{value:'no'}]"
            :disabled="isComplete"
        />
        <input-row 
            v-if="workingCopy.data.ongoing_plans_updated === 'yes'" 
            v-model="workingCopy.data.ongoing_plans_update_details" 
            class="ml-4" 
            label="Please explain" 
            :errors="errors.ongoing_plans_update_details"
            vertical
            type="large-text"
            :disabled="isComplete"
        />

        <input-row v-model="workingCopy.data.changes_to_call_frequency"
            vertical
            :disabled="isComplete"
            label="Have there been any changes to your VCEP’s workflow or meeting/call frequency in the last year?"
            type="radio-group"
            :errors="errors.changes_to_call_frequency"
            :options="[{value: 'yes'},{value: 'no'}]" 
        />

        <input-row 
            v-if="workingCopy.data.changes_to_call_frequency === 'yes'" 
            v-model="workingCopy.data.changes_to_call_frequency_details" 
            class="ml-4" 
            label="Please explain" 
            :errors="errors.changes_to_call_frequency_details"
            vertical
            type="large-text"
            :disabled="isComplete"
        />
    </ApplicationSection>
</template>