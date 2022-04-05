<template>
    <div class="application-review">
        <base-step 
            :step="1" 
            document-name="Scope and Membership Application" 
            title="Scope and Membership" 
            :document-type="1" 
            :document-gets-reviewed="true"
            approve-button-label="Approve Scope and Membership"
            @stepApproved="handleApproved"
        >
            <template v-slot:sections>
                <definition-review />
                <!-- <div class="application-section">
                    <member-list />
                    <div v-if="group.isVcep()">
                        <h3>Description of Expertise</h3>
                        <blockquote>
                            <span v-if="application.membership_description">{{application.membership_description}}</span>
                            <span v-else class="muted">{{'Pending...'}}</span>

                        </blockquote>
                    </div>
                </div>
                <div class="application-section">
                    <h2>Scope</h2>
                    <component :is="geneList" readonly :editing="false"></component>

                    <h3>Description of Scope</h3>
                    <blockquote>
                        <span v-if="application.scope_description">{{application.scope_description}}</span>
                        <span v-else class="muted">{{'Pending...'}}</span>
                    </blockquote>
                </div>
                <div class="application-section" v-if="group.isGcep()">
                    <h2>GCEP Attestation</h2>
                    <dictionary-row label="GCI Training Date">
                        <span v-if="application.scope_description">{{formatDate(application.gci_training_date)}}</span>
                        <span v-else class="muted">{{'Pending...'}}</span>
                    </dictionary-row>
                    <dictionary-row label="Signed On">
                        <span v-if="application.scope_description">{{formatDate(application.gcep_attestation_date)}}</span>
                        <span v-else class="muted">{{'Pending...'}}</span>
                    </dictionary-row>
                </div>

                <div class="application-section" v-if="group.isVcep()">
                    <h2>Reanalysis &amp; descrepency resolution signed on</h2>
                    <dictionary-row label="Signed on">
                        <span v-if="application.scope_description">
                            {{formatDate(application.reanalysis_attestation_date)}}
                        </span>
                        <span v-else class="muted">{{'Pending...'}}</span>
                    </dictionary-row>
                    <dictionary-row label="Plans that differ from expectations" v-if="application.reanalysis_other">
                        {{application.reanalysis_other}}
                    </dictionary-row>
                </div>

            <div class="applicaiton-section" v-if="group.isGcep()">
                <h2>Plans for Ongoing Review and Reanalysis and Discrepancy Resolution</h2>
                <gcep-ongoing-plans-form readonly />
            </div>

                <div class="application-section">
                    <h2>NHGRI Data Availability</h2>
                    <dictionary-row label="Signed on">
                        <span v-if="application.nhgri_attestation_date">
                            {{formatDate(application.nhgri_attestation_date)}}
                        </span>
                        <span v-else class="muted">{{'Pending...'}}</span>
                    </dictionary-row>
                </div>
 -->

            </template>

            <!-- <coi-log class="mb-6" :group="group" /> -->
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
        // geneList () {
        //     return this.group.isVcep() ? VcepGeneList : GcepGeneList;
        // }
    },
    methods: {
        handleApproved() {
            this.$emit('stepApproved')
        }
    }
}
</script>