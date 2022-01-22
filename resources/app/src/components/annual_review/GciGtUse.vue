<template>
    <div>
        <note>For additional information please contact <a href="mailto:clingentrackerhelp@unc.edu">clingentrackerhelp@unc.edu</a></note>
        <input-row
            :disabled="isComplete" 
            label="Does your Expert Panel use the GCI for all gene curation activities?"
            vertical :errors="errors.gci_use"
             type="radio-group"
            v-model="workingCopy.data.gci_use"
            :options="[{value:'yes'},{value:'no'}]"
        />
        <transition name="slide-fade-down">
            <input-row
                :disabled="isComplete" 
                v-if="workingCopy.data.gci_use === 'no'"
                v-model="workingCopy.data.gci_use_details" 
                :errors="errors.gci_use_details"
                label="Please explain"
                class="ml-4" 
                type="large-text"
                vertical
            />
        </transition>
        <hr>
        <input-row
            :disabled="isComplete"
            vertical
            label="Our complete gene list (genes previously approved or currently under consideration) has been added to the GeneTracker?"
            type="radio-group"
            v-model="workingCopy.data.gt_gene_list"
            :options="[{value:'yes'},{value:'no'}]"
        />
        <transition name="slide-fade-down">
            <input-row
                :disabled="isComplete" 
                v-if="workingCopy.data.gt_gene_list === 'no'"
                v-model="workingCopy.data.gt_gene_list_details" 
                :errors="errors.gt_gene_list_details"
                label="Please explain"
                class="ml-4" 
                type="large-text"
                vertical
            />
        </transition>
        <hr>
        <input-row
            :disabled="isComplete"
            label="All application precuration information has been added to the GeneTracker."
            vertical
            type="radio-group"
            v-model="workingCopy.data.gt_precuration_info"
            :options="[{value:'yes'},{value:'no'}]"
        />
        <transition name="slide-fade-down">
            <input-row
                :disabled="isComplete" 
                v-if="workingCopy.data.gt_precuration_info === 'no'"
                v-model="workingCopy.data.gt_precuration_info_details" 
                :errors="errors.gt_precuration_info_details"
                label="Please explain"
                class="ml-4" 
                type="large-text"
                vertical
            />
        </transition>
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'GcGtUse',
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