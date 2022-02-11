<template>
    <application-section title="Sustained Variant Curation">
        <vcep-ongoing-plans-form 
            v-model="workingCopy"
            :errors="errors"
            @updated="$emit('updated')"
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
    </application-section>
</template>
<script>
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
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
    emits: [ ...mirror.emits ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>