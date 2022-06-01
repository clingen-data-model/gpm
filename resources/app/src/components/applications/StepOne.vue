<template>
    <div class="application-review">
        <base-step 
            :step="1" 
            document-name="Scope and Membership Application" 
            title="Scope and Membership" 
            :document-type="1" 
            :document-gets-reviewed="true"
            approve-button-label="Approve Scope and Membership"
            @updated="this.$emit('updated')"
        >
            <template v-slot:sections>
                <definition-review />
            </template>
        </base-step>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from '@/components/applications/BaseStep.vue'
// import CoiLog from '@/components/applications/COILog.vue'
// import MemberList from '@/components/groups/MemberList.vue'
// import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
// import GcepGeneList from '@/components/expert_panels/GcepGeneList.vue'
// import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm.vue';
// import VcepGeneList from '@/components/expert_panels/VcepGeneList.vue'
import DefinitionReview from '@/components/expert_panels/DefinitionReview.vue'

export default {
    name: 'StepOne',
    components: {
        BaseStep,
        // CoiLog,
        // MemberList,
        // ApplicationSection,
        // GcepOngoingPlansForm,
        DefinitionReview
    },
    emits: ['approved', 'updated'],
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
        // geneList () {
        //     return this.group.isVcep() ? VcepGeneList : GcepGeneList;
        // }
    },
    methods: {
    }
}
</script>