<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import SubmitterInformation from '@/components/annual_review/SubmitterInformation'
import GciGtUse from '@/components/annual_review/GciGtUse'
import GeneCurationTotals from '@/components/annual_review/GeneCurationTotals'
import {debounce} from 'lodash'
import VcepOngoingPlansUpdateForm from '@/components/annual_review/VcepOngoingPlansUpdateForm'
import VcepRereviewForm from '@/components/annual_review/VcepRereviewForm'
import GcepRereviewForm from '@/components/annual_review/GcepRereviewForm'
import GoalsForm from '@/components/annual_review/GoalsForm'
import FundingForm from '@/components/annual_review/FundingForm'
import GcepOngoingPlansUpdateForm from '@/components/annual_review/GcepOngoingPlansUpdateForm'
import VciUse from '@/components/annual_review/VciUse'
import SpecificationProgress from '@/components/annual_review/SpecificationProgress'
import VcepTotals from '@/components/annual_review/VcepTotals'
import VariantReanalysis from '@/components/annual_review/VariantReanalysis'
import MemberDesignationUpdate from '@/components/annual_review/MemberDesignationUpdate'
import VcepPlansForSpecifications from '@/components/annual_review/VcepPlansForSpecifications'
import VariantCurationWorkflow from '@/components/annual_review/VariantCurationWorkflow'
import MembershipUpdate from '@/components/annual_review/MembershipUpdate'

export default {
    name: 'AnnualReviewForm',
    components: {
        'app-section': ApplicationSection,
        SubmitterInformation,
        GciGtUse,
        GeneCurationTotals,
        VcepOngoingPlansUpdateForm,
        GcepRereviewForm,
        VcepRereviewForm,
        GoalsForm,
        FundingForm,
        GcepOngoingPlansUpdateForm,
        VciUse,
        SpecificationProgress,
        VcepTotals,
        VariantReanalysis,
        MemberDesignationUpdate,
        VcepPlansForSpecifications,
        VariantCurationWorkflow,
        MembershipUpdate
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            annualReview: {
                submitter_id: null,
                grant: null,
                ep_activity: null,
                submitted_inactive_form: null,
                membership_attestation: null,
                applied_for_funding: null,
                funding: null,
                funding_other_details: null,
                funding_thoughts: null,
                website_attestation: null,
                ongoing_plans_updated: null,
                ongoing_plans_update_details: null,
                //GCEP
                gci_use: null,
                gci_use_details: null,
                gt_gene_list: null,
                gt_gene_list_details: null,
                gt_precuration_info: null,
                gt_precuration_info_details: null,
                published_count: null,
                approved_unpublished_count: null,
                in_progress_count: null,
                recuration_begun: null,
                recuration_designees: null,
                //VCEP
                vci_use: null,
                vci_use_details: null,
                goals: null,
                cochair_commitment: null,
                cochair_commitment_details: null,
                sepcification_progress: null,
                specification_url: null,
                variant_counts: [],
                variant_workflow_changes: null,
                variant_workflow_changes_details: null,
                specification_progress: null,
                specification_progress_url: null,
                specification_plans: null,
                specification_plans_details: null,
                rereview_discrepencies_progress: null,
                rereview_lp_and_vus_progress: null,
                rereview_lb_progress: null,
                member_designation_changed: null,
            },
            errors: {},
            throttle: 1000,
            showModal: false
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        rereviewForm () {
            return this.group.isVcep() ? VcepRereviewForm : GcepRereviewForm;
        },
        year () {
            const thisYear = (new Date()).getFullYear()-1;
            return thisYear;
        }

    },
    watch: {
        annualReview: function () {
            this.debounceSave();
        },
        'group.expert_panel.curation_review_protocol_id': {
            handler (from) {
                if (from) {
                    this.saveOngoingPlans();
                }
            }
        },
        'group.expert_panel.curation_review_protocol_other': {
            handler (from) {
                if (from) {
                    this.saveOngoingPlans();
                }
            }
        },
        $route() {
            this.showModal = this.$route.meta.showModal 
                                ? Boolean(this.$route.meta.showModal) 
                                : false;
        }
    },
    methods: {
        submit () {
            console.log('submit');
        },
        hideModal () {
            this.$router.replace({name: 'AnnualReview', params: {uuid: this.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'AnnualReview', params: {uuid: this.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
    },
    created () {
        this.debounceSave = debounce(() => {
            console.log('debounceSave', this.annualReview);
            console.log('Remember to submit refs', this.$refs)
        }, this.throttle);

        this.saveOngoingPlans = debounce(() => {
            const {uuid, expert_panel: expertPanel} = this.group;
            return this.$store.dispatch('groups/curationReviewProtocolUpdate', {uuid, expertPanel});
        }, 5000);
    },
    mounted () {
        this.$store.dispatch('groups/findAndSetCurrent', this.uuid)
            .then(response => console.log('got group', response.data.data));
    }
}
</script>
<template>
    <div class="annual-review flex">
        <div class="w-1/3 overflow-hidden flex-shrink-0 flex-grow-0">
            <pre>{{annualReview}}</pre>
        </div> -->
        <div class="overflow-scroll" v-remaining-height>
        <!-- <div> -->
        <router-link class="note"
            :to="{name: 'GroupDetail', params: {uuid: group.uuid}}"
            v-if="group.uuid"
        >
            {{group.displayName}}
        </router-link>

        <h1>{{group.displayName}} - Annual Review for {{year}}</h1>

        <submitter-information v-model="annualReview" :errors="errors" />

        <transition name="slide-fade-down">
            <div v-if="group.isVcep() || group.isGcep() && annualReview.ep_activity == 'active' ">
                <membership-update v-model="annualReview" :errors="errors" />

                <template v-if="group.isGcep()">
                    <app-section title="Use of GCI and GeneTracker Systems">
                        <gci-gt-use v-model="annualReview" :errors="errors" />
                    </app-section>

                    <app-section title="Summary of total numbers of genes curated">
                        <gene-curation-totals v-model="annualReview" :errors="errors" />
                    </app-section>

                    <app-section title="Changes to plans for ongoing curation">
                        <gcep-ongoing-plans-update-form v-model="annualReview" :errors="errors" @updated="saveOngoingPlans"></gcep-ongoing-plans-update-form>
                    </app-section>

                    <app-section title="Gene Re-curation/Re-review">
                        <gcep-rereview-form v-model="annualReview" :errors="errors"></gcep-rereview-form>
                    </app-section>
                </template>

                <app-section v-if="group.isVcep()" title="Use of Variant Curation Interface (VCI)">
                    <vci-use v-model="annualReview" :errors="errors"></vci-use>
                </app-section>

                <app-section title="Goals for next year">
                    <goals-form v-model="annualReview" :errors="errors" />
                </app-section>

                <app-section title="Additional Funding">
                    <funding-form v-model="annualReview" :errors="errors" />
                </app-section>

                <app-section title="Webpage Updates">
                    <input-row :errors="errors.website_attestation" vertical>
                        <template v-slot:label>
                            <p>
                                Please review your ClinGen EP webpage, including Expert Panel Status, description, membership, COI and relevant documentation, including publications. See the <a href="https://docs.google.com/document/d/1GeyR1CBqlzLHOdlPLJt0uA29Z-2ysmTX1dtH9PDmqRo/edit?usp=sharing">Coordinator Resource Document</a> for instructions on how to update web pages.
                            </p>
                        </template>
                        <checkbox 
                            label="I attest that the information on the webpage is up-to-date and accurate." 
                            v-model="annualReview.website_attestation"
                        />
                    </input-row>
                </app-section>

                <template v-if="group.isVcep() && group.expert_panel.defIsApproved">
                    <dev-component>Begin questions for specifcation-ed VCEPS</dev-component>
                    <app-section title="Progress on Rule Specification">
                        <specification-progress v-model="annualReview" :errors="errors" />
                    </app-section>

                    <app-section title="Summary of total number of variants curated">
                        <vcep-totals v-model="annualReview" :errors="errors" />
                    </app-section>
                    <dev-component>End questions for specifcation-ed VCEPS</dev-component>
                </template>

                <template v-if="group.isVcep() && group.expert_panel.pilotSpecificationsIsApproved">
                    <dev-component>Begin Questions for sustained curation</dev-component>
                    
                    <variant-curation-workflow v-model="annualReview" :errors="errors" />
                    
                    <variant-reanalysis v-model="annualReview" :errors="errors" />

                    <vcep-ongoing-plans-update-form v-model="annualReview" :errors="errors" />
                    
                    <member-designation-update 
                        v-model="annualReview" 
                        :errors="errors"
                        @updated="debounceSave"
                        ref="memberDesignationUpdate"
                    />

                    <vcep-plans-for-specifications 
                        v-model="annualReview" 
                        :errors="errors" 
                    />

                    <dev-component>End Questions for sustained curation</dev-component>
                </template>

                <hr>
                <button class="btn btn-lg" @click="submit">Submit annual update</button>
                <br>
            </div>
        </transition>
</div>
        <teleport to='body'>
            <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
        </teleport>
    </div>
</template>
<style lang="postcss">
    .annual-review .input-row .label-container{
        font-size: 1.05em;
        margin-bottom: .5rem;
    }
    .annual-review .application-section {
        padding-bottom: .25rem;
    }
</style>
