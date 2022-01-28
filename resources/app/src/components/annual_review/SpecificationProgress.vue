<template>
    <div>
        <input-row 
            :disabled="isComplete"
            label="Link to approved specification in Cspec" 
            :errors="errors.specification_progress_url" 
            vertical
            class="ml-4"
            v-model="workingCopy.data.specification_progress_url"
        />
        <input-row 
            :disabled="isComplete"
            v-model="workingCopy.data.specification_progress"
            type="radio-group"
            :options="[
                {   value: 'in-progress',  
                    label:'VCEP specifications to the ACMG/AMP guidelines in progress.'
                },
                {
                    value: 'no-changes',
                    label: 'No Changes to the ClinGen-approved VCEP specifications to the ACMG/AMP guidelines (for ClinGen 3-star VCEPs only).'
                },
                {
                    value: 'changes-made',
                    label: 'We have made changes to the ClinGen-approved  VCEP specification to the ACMG/AMP guidelines(for ClinGen 3-star VCEPs only)'
                }
            ]"
            :errors="errors.specification_progress" 
            vertical
            label="Have you made any changes or additions to your ACMG/AMP specifications for the gene(s) of interest, including evidence and rationale to support the rule specifications."
        />
        <transition name="slide-fade-down">
            <input-row 
                v-if="workingCopy.specification_progress === 'changes-made'"
                label="Describe changes" 
                v-model="workingCopy.specification_progress_details" 
                :errors="errors.specification_progress_details"
                type="large-text"
                vertical
            />
        </transition>
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'SpecificationProgress',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
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