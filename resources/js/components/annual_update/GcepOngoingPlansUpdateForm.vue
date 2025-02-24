<script>
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm.vue'
import mirror from '@/composables/setup_working_mirror'


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
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        isComplete () {
            return Boolean(this.modelValue.completed_at);
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
            v-model="workingCopy.data.ongoing_plans_updated"
            :disabled="isComplete"
            label="Does this current review method represent a change from previous years?"
            :errors="errors.ongoing_plans_updated"
            type="radio-group"
            :options="[{value:'yes'},{value:'no'}]"
            vertical 
        />
        <input-row 
            v-if="workingCopy.data.ongoing_plans_updated === 'yes'"
            v-model="workingCopy.data.ongoing_plans_update_details" 
            :disabled="isComplete" 
            class="ml-4" 
            label="Please explain" 
            :errors="errors.ongoing_plans_update_details"
            vertical
            type="large-text"
        />
    </div>
</template>