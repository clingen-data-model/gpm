<template>
    <div>
        <p>
            In reviewing the GCEPs progress over the last year ({{lastYear}}), please answer the following questions. Note, This information can be accessed from the GCI, GeneTracker, and in some instances (e.g. published curations) from the ClinGen website.
            This information can be downloaded from the <gene-tracker-link /> or the <gci-link />. 
            <br>
            Please cross reference <website-link /> for published records.
        </p>
        <input-row
            :disabled="isComplete" 
            label="Approved and published on the ClinGen Website." 
            type="number" 
            v-model="workingCopy.data.published_count"
            :errors="errors.published_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
        <input-row
            :disabled="isComplete" 
            label="Approved and pending publishing on the Clingen Website." 
            type="number" 
            v-model="workingCopy.data.approved_unpublished_count"
            :errors="errors.approved_unpublished_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
        <input-row
            :disabled="isComplete" 
            label="In progress in the GCI." 
            type="number" 
            v-model="workingCopy.data.in_progress_count"
            :errors="errors.in_progress_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'GeneCurationTotals',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [  ...mirror.emits ],
    computed: {
        lastYear () {
            return (new Date()).getFullYear()-1;
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