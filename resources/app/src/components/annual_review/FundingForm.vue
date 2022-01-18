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
            label="" :errors="errors.funding"
            class="ml-4" 
            vertical
        >
            <select v-model="workingCopy.funding">
                <option value="NIH U24">NIH U24</option>
                <option value="Professional society">Professional society</option>
                <option value="Patient Advocacy Group">Patient Advocacy Group</option>
                <option value="Pharma">Pharma</option>
                <option value="Industry">Industry</option>
                <option value="Other">Other</option>
            </select>
        </input-row>

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
    emits: [ ...mirror.emits ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>