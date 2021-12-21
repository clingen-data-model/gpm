<template>
    <base-step 
        :step="2"
        :document-type="2"
        document-name="Draft Specifications"
        :document-gets-reviewed="false"
        approve-button-label="Approve Draft Specifications"
        title="Draft ACMG Guideline Specifications"
        @stepApproved="$emit('stepApproved')"
    >
        <template v-slot:sections>
            <div class="application-section">
                <h2>Draft Specifications</h2>
                <cspec-summary></cspec-summary>
            </div>
        </template>
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from '@/components/applications/BaseStep'
import CspecSummary from '@/components/expert_panels/CspecSummary'

export default {
    name: 'StepTwo',
    components: {
        BaseStep,
        CspecSummary
    },
    props: {
        
    },
    emits: ['stepApproved'],
    data() {
        return {
            
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
    },
    methods: {

    }
}
</script>