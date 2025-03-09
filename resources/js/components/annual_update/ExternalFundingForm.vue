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
            v-model="workingCopy.data.external_funding"
            type="radio-group"
            :options="[{value: 'yes'}, {value: 'no'}]"
            label="Does this expert panel currently have external funding to directly support curation activities (e.g. NIH U24, industry, etc.)?"
            :errors="errors.external_funding"
            vertical
        />

        <input-row
            :disabled="isComplete"
            v-if="workingCopy.data.external_funding == 'yes'"
            label="Funding Type" :errors="errors.external_funding_type"
            class="ml-4"
            type="select"
            v-model="workingCopy.data.external_funding_type"
            :options="fundingOptions"
        />

        <input-row
            :disabled="isComplete"
            v-if="workingCopy.data.external_funding_type == 'Other'"
            type="large-text"
            v-model="workingCopy.data.external_funding_other_details"
            :errors="errors.external_funding_other_details"
            placeholder="details"
            class="ml-8"
            vertical
        />

        <input-row
            :disabled="isComplete"
            v-model="workingCopy.data.funding_plans"
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
            :disabled="isComplete"
            v-model="workingCopy.data.funding_plans_details"
            type="large-text"
            v-if="workingCopy.data.funding_plans == 'yes'"
            label="Please describe pending applications or plans for future funding."
            :errors="errors.funding_plans_details"
            vertical
            class="ml-4"
        />

    </div>
</template>
