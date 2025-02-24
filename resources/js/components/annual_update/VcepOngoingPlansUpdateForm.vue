<template>
    <ApplicationSection title="Sustained Variant Curation">
        <VcepOngoingPlansForm 
            v-model="workingCopy"
            :errors="errors"
            @update="$emit('updated')"
            :readonly="isComplete"
        />
        <input-row vertical 
            label="Does this current review method represent a change from previous years?"
            :errors="errors.ongoing_plans_updated"
            type="radio-group"
            v-model="workingCopy.data.ongoing_plans_updated"
            :options="[{value:'yes'},{value:'no'}]"
            :disabled="isComplete"
        />
        <input-row 
            v-if="workingCopy.data.ongoing_plans_updated == 'yes'" 
            class="ml-4" 
            label="Please explain" 
            :errors="errors.ongoing_plans_update_details" 
            vertical
            type="large-text"
            v-model="workingCopy.data.ongoing_plans_update_details"
            :disabled="isComplete"
        />

        <input-row vertical
            :disabled="isComplete"
            label="Have there been any changes to your VCEP’s workflow or meeting/call frequency in the last year?"
            type="radio-group"
            v-model="workingCopy.data.changes_to_call_frequency"
            :errors="errors.changes_to_call_frequency"
            :options="[{value: 'yes'},{value: 'no'}]" 
        />

        <input-row 
            v-if="workingCopy.data.changes_to_call_frequency == 'yes'" 
            class="ml-4" 
            label="Please explain" 
            :errors="errors.changes_to_call_frequency_details" 
            vertical
            type="large-text"
            v-model="workingCopy.data.changes_to_call_frequency_details"
            :disabled="isComplete"
        />
    </ApplicationSection>
</template>
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
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at);
        }
    },
    emits: [ ...mirror.emits, 'updated'],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>