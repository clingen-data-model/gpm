<template>
    <div>
        <p>
            This information can be downloaded from the <a href="https://gene-tracker.clinicalgenome.org">Gene Tracker</a> or the <a href="https://curation.clinicalgenome.org/dashboard">Gene Curation Interface</a>. 
            <br>
            Please cross reference ww.clinicalgenome.org for published records."	
        </p>
        <input-row 
            label="Approved and posted on the ClinGen Website." 
            type="number" 
            v-model="workingCopy.published_count"
            :errors="errors.published_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
        <input-row 
            label="Approved and pending posting on the Clingen Website." 
            type="number" 
            v-model="workingCopy.approved_unpublished_count"
            :errors="errors.approved_unpublished_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
        <input-row 
            label="In progress in the GCI." 
            type="number" 
            v-model="workingCopy.in_progress_count"
            :errors="errors.in_progress_count" 
            labelWidthClass="w-80"
            input-class="w-16"
        />
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror'

export default {
    name: 'GeneCurationTotals',
    props: {
        modelValue: {
            required: true,
            type: Object
        },
        errors: {
            required: true,
            type: Object
        }
    },
    setup (props, context) {
        const { workingCopy } = setupWorkingMirror(props, context);
        return {
            workingCopy
        };
    },
}
</script>