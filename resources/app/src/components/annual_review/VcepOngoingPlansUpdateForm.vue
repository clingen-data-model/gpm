<template>
    <div>
        <vcep-ongoing-plans-form 
            v-model="workingCopy"
            :errors="errors"
            @updated="$emit('updated')"
        />
        <input-row vertical 
            label="Does this current review method represent a change from previous years?"
            :errors="errors.ongoing_plans_updated"
        >
            <div class="ml-4">
                <radio-button v-model="workingCopy.ongoing_plans_updated" value="yes">Yes</radio-button>
                <input-row v-if="workingCopy.ongoing_plans_updated == 'yes'" class="ml-4" label="Please explain" :errors="errors.ongoing_plans_update_details" vertical>
                    <textarea rows="5" class="w-full" v-model="workingCopy.ongoing_plans_update_details"></textarea>
                </input-row>
                <radio-button v-model="workingCopy.ongoing_plans_updated" value="no">No</radio-button>
            </div>
        </input-row>
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror'
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm'

export default {
    name: 'GCEPReviewForm',
    components: {
        VcepOngoingPlansForm,
    },
    emits: [
        'updated'
    ],
    props: {
        modelValue: {
            type: Object,
            required: true
        },
        errors: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
    },
    setup(props, context) {
        const {workingCopy} = setupWorkingMirror(props, context);
        console.log(workingCopy)
        return {
            workingCopy
        }
    }
}
</script>