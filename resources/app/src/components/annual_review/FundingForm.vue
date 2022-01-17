<template>
    <div>
        <input-row 
            label="Have you applied for external funding for your EP?" 
            :errors="errors.applied_for_additional_funding" 
            vertical
        >
            <div class="ml-4">
                <radio-button v-model="workingCopy.applied_for_funding" value="yes">
                    Yes
                </radio-button>
                <div>
                    <input-row label="" :errors="errors.funding" class="ml-4" vertical v-if="workingCopy.applied_for_funding == 'yes'">
                        <select v-model="workingCopy.funding">
                            <option value="NIH U24">NIH U24</option>
                            <option value="Professional society">Professional society</option>
                            <option value="Patient Advocacy Group">Patient Advocacy Group</option>
                            <option value="Pharma">Pharma</option>
                            <option value="Industry">Industry</option>
                            <option value="Other">Other</option>
                        </select>
                        &nbsp;
                        <input v-if="workingCopy.funding == 'Other'" type="text" v-model="workingCopy.funding_other_detail" placeholder="details">
                    </input-row>
                </div>
                <radio-button v-model="workingCopy.applied_for_funding" value="no">No</radio-button>

                <input-row label="Please describe any thoughts, ideas, or plans for soliciting funding or personnel (in addition to any existing funding/support you may have)." :errors="errors.funding_thoughts" vertical class="ml-4" v-if="workingCopy.applied_for_funding == 'no'">
                    <textarea v-model="workingCopy.funding_thoughts" rows="5" class="w-full"></textarea>
                </input-row>

            </div>
        </input-row>
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'FundingForm',
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
    }
}
</script>