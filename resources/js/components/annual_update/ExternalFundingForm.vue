<script>
import mirror from '@/composables/setup_working_mirror'
// Prior version of this (before 2024 annual update) was "FundingForm.vue"

export default {
    name: 'ExternalFundingForm',
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
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
    }
}
</script>

<template>
    <div>
        <input-row
            v-model="workingCopy.data.external_funding"
            :disabled="isComplete"
            type="radio-group"
            :options="[{value: 'yes'}, {value: 'no'}]"
            label="Does this expert panel currently have external funding to directly support curation activities (e.g. NIH U24, industry, etc.)?"
            :errors="errors.external_funding"
            vertical
        />

        <input-row
            v-if="workingCopy.data.external_funding == 'yes'"
            v-model="workingCopy.data.external_funding_type"
            :disabled="isComplete" label="Funding Type"
            :errors="errors.external_funding_type"
            class="ml-4"
            type="select"
            :options="fundingOptions"
        />

        <input-row
            v-if="workingCopy.data.external_funding_type == 'Other'"
            v-model="workingCopy.data.external_funding_other_details"
            :disabled="isComplete"
            type="large-text"
            :errors="errors.external_funding_other_details"
            placeholder="details"
            class="ml-8"
            vertical
        />

        <input-row
            v-model="workingCopy.data.funding_plans"
            :disabled="isComplete"
            type="radio-group"
            :options="[{value: 'yes'}, {value: 'no'}]"
            :errors="errors.funding_plans"
            vertical
        >
            <template #label>
                Does this expert panel
                <ul class="list-disc list-inside ml-8">
                    <li>have a pending application for external funding, or </li>
                    <li>have plans to submit a proposal for external funding in the next year?</li>
                </ul>
            </template>
        </input-row>

        <input-row
            v-if="workingCopy.data.funding_plans == 'yes'"
            v-model="workingCopy.data.funding_plans_details"
            :disabled="isComplete"
            type="large-text"
            label="Please describe pending applications or plans for future funding."
            :errors="errors.funding_plans_details"
            vertical
            class="ml-4"
        />

    </div>
</template>
