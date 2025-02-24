<script>
import mirror from '@/composables/setup_working_mirror'
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm.vue'


export default {
    name: 'GCEPReviewForm',
    components: {
        GcepOngoingPlansForm
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits ],
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        isComplete () {
            return Boolean(this.modelValue.completed_at);
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
    <div>
        <GcepOngoingPlansForm 
            v-model="workingCopy"
            :errors="errors"
            @updated="$emit('updated')"
        />
        <input-row 
            :disabled="isComplete"
            label="Does this current review method represent a change from previous years?"
            :errors="errors.ongoing_plans_updated"
            type="radio-group"
            v-model="workingCopy.data.ongoing_plans_updated"
            :options="[{value:'yes'},{value:'no'}]"
            vertical 
        />
        <input-row 
            :disabled="isComplete"
            v-if="workingCopy.data.ongoing_plans_updated === 'yes'" 
            class="ml-4" 
            label="Please explain" 
            :errors="errors.ongoing_plans_update_details" 
            vertical
            type="large-text"
            v-model="workingCopy.data.ongoing_plans_update_details"
        />
    </div>
</template>