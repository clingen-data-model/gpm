<template>
    <base-step 
        :step="4"
        :document-type="5"
        document-name="Sustained Curation"
        :document-gets-reviewed="true"
        approve-button-label="Approve Sustained Curation"
        title="Sustained Curation"
        @stepApproved="$emit('stepApproved')"
    >
        <template v-slot:sections>
            <div class="applicaiton-section">
                <h2>Plans for Ongoing Review and Reanalysis and Discrepancy Resolution</h2>
                <vcep-ongoing-plans-form readonly />
            </div>
            <div class="application-section">
                <h2>Example Evidence Summaries</h2>
                <evidence-summary-list readonly/>
            </div>
            <div class="application-section">
                <h2>Member Designation</h2>
                <member-designation-form readonly />
            </div>
        </template>
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from '@/components/applications/BaseStep'
import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList'
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm'
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm';

export default {
    name: 'StepFour',
    components: {
        BaseStep,
        EvidenceSummaryList,
        VcepOngoingPlansForm,
        MemberDesignationForm,
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