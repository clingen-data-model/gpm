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
<template>
    <div>

        <p>
            In reviewing the GCEPs progress over the last year ({{lastYear}}), please answer the following questions. Note, this information can be accessed from the GCI, GeneTracker, and in some instances (e.g. published curations) from the ClinGen website.
            This information can be downloaded from the <gene-tracker-link /> or the <gci-link />. 
            <br>
            Please cross reference <website-link /> for published records.  Estimates are acceptable.
        </p>
        <input-row
            :disabled="isComplete" 
            label="Published on the ClinGen Website.  (curations with a “date last evaluated” from Jan 1 to Dec 31 of the prior year)
" 
            type="number" 
            v-model="workingCopy.data.published_count"
            :errors="errors.published_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
        <input-row
            :disabled="isComplete" 
            label="Entered in the GCI, not published to the ClinGen website (i.e. records with the following statuses: “in progress;” “provisional;” “approved/not published”)
" 
            type="number" 
            v-model="workingCopy.data.approved_unpublished_count"
            :errors="errors.approved_unpublished_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
        <input-row
            :disabled="isComplete" 
            label="Curations not entered in the GCI (e.g., reviewed on calls but data not entered in the ClinGen systems)
" 
            type="number" 
            v-model="workingCopy.data.in_progress_count"
            :errors="errors.in_progress_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />


        <input-row
            :disabled="isComplete" 
            label="Please describe if there are any issues with the publishing of curations." 
            type="large-text" 
            v-model="workingCopy.data.publishing_issues"
            :errors="errors.publishing_issues" 
            labelWidthClass="w-80"
            input-class="w-120"
        />

        
        
        
    </div>
</template>