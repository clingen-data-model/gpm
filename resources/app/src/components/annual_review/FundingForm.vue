<template>
    <div>
        <input-row 
            v-model="workingCopy.applied_for_funding"
            type="radio-group"
            :options="[{value: 'yes'}, {value: 'no'}]"
            label="Have you applied for external funding for your EP?" 
            :errors="errors.applied_for_additional_funding" 
            vertical
        />

        <input-row 
            v-if="workingCopy.applied_for_funding == 'yes'"
            label="Funding Type" :errors="errors.funding"
            class="ml-4"
            type="select"
            v-model="workingCopy.funding"
            :options="fundingOptions"
        />

        <input-row 
            v-if="workingCopy.funding == 'Other'" 
            type="large-text" 
            v-model="workingCopy.funding_other_details" 
            :errors="errors.funding_other_details"
            placeholder="details"
            class="ml-8"
            vertical
        />

        <input-row 
            v-model="workingCopy.funding_thoughts"
            type="large-text"
            v-if="workingCopy.applied_for_funding == 'no'"
            label="Please describe any thoughts, ideas, or plans for soliciting funding or personnel (in addition to any existing funding/support you may have)." 
            :errors="errors.funding_thoughts" 
            vertical 
            class="ml-4" 
        />

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
    data() {
        return {
            fundingOptions: [
                {value: 'NIH U24'},
                {value: 'Professional society'},
                {value: 'Patient Advocacy Group'},
                {value: 'Pharma'},
                {value: 'Industry'},
                {value: 'Other'},
            ]
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