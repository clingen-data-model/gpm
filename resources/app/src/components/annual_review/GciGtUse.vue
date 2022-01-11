<template>
    <div>
        <note>For additional information please contact <a href="mailto:clingentrackerhelp@unc.edu">clingentrackerhelp@unc.edu</a></note>
        <input-row 
            label="Does your Expert Panel use the GCI for all gene curation activities?"
            vertical :errors="errors.gci_use"
        >
            <div class="radio-group">
                <radio-button v-model="workingCopy.gci_use" label="Yes, use the GCI for all curation activities" :value="1" />
                <radio-button v-model="workingCopy.gci_use" label="No" :value="0" />
            </div>
        </input-row>
        <transition name="slide-fade-down">
            <input-row 
                v-if="workingCopy.gci_use === 0"
                v-model="workingCopy.gci_use_no_explanation" 
                :errors="errors.gci_use_no_explanation"
                label="Please explain"
                class="ml-6" 
            />
        </transition>
        <hr>
        <input-row
            vertical
            label="Our complete gene list (genes previously approved or currently under consideration) has been added to the GeneTracker?"
        >
            <div class="radio-group">
                <radio-button v-model="workingCopy.gt_gene_list" label="Yes, use the GCI for all curation activities" :value="1" />
                <radio-button v-model="workingCopy.gt_gene_list" label="No" :value="0" />
            </div>
        </input-row>
        <transition name="slide-fade-down">
            <input-row 
                v-if="workingCopy.gt_gene_list === 0"
                v-model="workingCopy.gt_gene_list_explanation" 
                :errors="errors.gt_gene_list_explanation"
                label="Please explain"
                class="ml-6" 
            />
        </transition>
        <hr>
        <input-row
            label="All application precuration information has been added to the GeneTracker."
            vertical
        >
            <div class="radio-group">
                <radio-button v-model="workingCopy.gt_precuration_info" label="Yes, use the GCI for all curation activities" :value="1" />
                <radio-button v-model="workingCopy.gt_precuration_info" label="No" :value="0" />
            </div>
        </input-row>
        <transition name="slide-fade-down">
            <input-row 
                v-if="workingCopy.gt_precuration_info === 0"
                v-model="workingCopy.gt_precuration_info_explanation" 
                :errors="errors.gt_precuration_info_explanation"
                label="Please explain"
                class="ml-6" 
            />
        </transition>
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror'

export default {
    name: 'GciGtUse',
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