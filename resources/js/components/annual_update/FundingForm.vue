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
                {value: 'NIH U24', label: 'NIH U24'},
                {value: 'Professional society'},
                {value: 'Patient Advocacy Group'},
                {value: 'Pharma'},
                {value: 'Industry'},
                {value: 'Other'},
            ]
        }
    },
    emits: [ ...mirror.emits ],
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
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
        <input-row
            :disabled="isComplete"
            v-model="workingCopy.data.applied_for_funding"
            type="radio-group"
            :options="[{value: 'yes'}, {value: 'no'}]"
            label="Have you applied for external funding to directly support the work of your EP (e.g. NIH U24, industry, etc.)?"
            :errors="errors.applied_for_funding"
            vertical
        />

        <input-row
            :disabled="isComplete"
            v-if="workingCopy.data.applied_for_funding === 'yes'"
            label="Funding Type" :errors="errors.funding"
            class="ml-4"
            type="select"
            v-model="workingCopy.data.funding"
            :options="fundingOptions"
        />

        <input-row
            :disabled="isComplete"
            v-if="workingCopy.data.funding === 'Other'"
            type="large-text"
            v-model="workingCopy.data.funding_other_details"
            :errors="errors.funding_other_details"
            placeholder="details"
            class="ml-8"
            vertical
        />

        <input-row
            :disabled="isComplete"
            v-model="workingCopy.data.funding_thoughts"
            type="large-text"
            v-if="workingCopy.data.applied_for_funding === 'no'"
            label="Please describe any thoughts, ideas, or plans for soliciting funding or personnel (in addition to any existing funding/support you may have)."
            :errors="errors.funding_thoughts"
            vertical
            class="ml-4"
        />

    </div>
</template>
