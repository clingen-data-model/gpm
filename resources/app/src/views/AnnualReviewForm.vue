<script>
import { debounce } from 'lodash'
import { api, isValidationError } from '@/http'

// Common Components
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import SubmitterInformation from '@/components/annual_review/SubmitterInformation'
import GoalsForm from '@/components/annual_review/GoalsForm'
import FundingForm from '@/components/annual_review/FundingForm'
import MemberDesignationUpdate from '@/components/annual_review/MemberDesignationUpdate'
import MembershipUpdate from '@/components/annual_review/MembershipUpdate'

// GCEP Components
import GciGtUse from '@/components/annual_review/GciGtUse'
import GcepRereviewForm from '@/components/annual_review/GcepRereviewForm'
import GcepOngoingPlansUpdateForm from '@/components/annual_review/GcepOngoingPlansUpdateForm'
import GeneCurationTotals from '@/components/annual_review/GeneCurationTotals'

// VCEP Components
import VariantCurationWorkflow from '@/components/annual_review/VariantCurationWorkflow'
import VciUse from '@/components/annual_review/VciUse'
import VcepOngoingPlansUpdateForm from '@/components/annual_review/VcepOngoingPlansUpdateForm'
import VcepPlansForSpecifications from '@/components/annual_review/VcepPlansForSpecifications'
import VcepTotals from '@/components/annual_review/VcepTotals'
import VariantReanalysis from '@/components/annual_review/VariantReanalysis'
import SpecificationProgress from '@/components/annual_review/SpecificationProgress'

export default {
    name: 'AnnualReviewForm',
    components: {
        'app-section': ApplicationSection,
        SubmitterInformation,
        GciGtUse,
        GeneCurationTotals,
        VcepOngoingPlansUpdateForm,
        GcepRereviewForm,
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
        MembershipUpdate,
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
                expert_panel: {
                    group: {
                        members: [],
                        coordinators: []
                    }
                },
                window: {},
                submitter_id: null,
                submitter: {
                    person: {},
                },
                data: {
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
                    specification_progress_url: null,
                    specification_progress: null,
                    specification_progress_details: null,
                    specification_plans: null,
                    specification_plans_details: null,
                    rereview_discrepencies_progress: null,
                    rereview_lp_and_vus_progress: null,
                    rereview_lb_progress: null,
                    member_designation_changed: null,
                }
            },
            errors: {},
            throttle: 1000,
            showModal: false,
            loading: false,
            saving: false,
            lastSaved: null
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        year () {
            const thisYear = this.annualReview.window ? this.annualReview.window.for_year : (new Date()).getFullYear()-1;
            return thisYear;
        },
        hasErrors () {
            return Object.keys(this.errors).length > 0;
        },
        window () {
            return this.annualReview.window || {}
        },
        dueDateAlertVariant () {
            return 'info';
        },
        expertPanel () {
            return this.annualReview.expert_panel || {};
        },
        groupDetailRoute () {
            return {name: 'GroupDetail', params: {uuid: this.group.uuid}}
        }
    },
    watch: {
        annualReview: {
            handler: function () {
                this.debounceSave();
            },
            deep: true
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
        async submit () {
            this.saving = true;
            this.errors = {};
            try {
                const updatedAnnualReview = await api.post(`/api/groups/${this.group.uuid}/expert-panel/annual-reviews/${this.annualReview.id}`)
                    .then(response => response.data);
                this.annualReview.completed_at = updatedAnnualReview.completed_at;
                this.saving = false;
            } catch (error) {
                this.saving = false;
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw error
            }
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
        async getAnnualReview () {
            this.annualReview = await api.get(`/api/groups/${this.group.uuid}/expert-panel/annual-reviews`)
                .then(response => {
                    const mergedData = {...this.annualReview.data, ...response.data.data}
                    const reviewData = response.data;
                    reviewData.data = mergedData;
                    return reviewData;
                });
            this.lastSaved = new Date(Date.parse(this.annualReview.updated_at));
        },
        async save() {
            if (!this.annualReview.id)  return;
            try {
                this.saving = true;
                await api.put( `/api/groups/${this.group.uuid}/expert-panel/annual-reviews/${this.annualReview.id}`,  this.annualReview);
                this.saving = false;
                this.lastSaved = new Date();
            } catch (error) {
                this.saving = false;
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors
                }
                throw error
            }
        }
    },
    created () {
        this.debounceSave = debounce(async () => this.save(), this.throttle);

        this.saveOngoingPlans = debounce(() => {
            const {uuid, expert_panel: expertPanel} = this.group;
            return this.$store.dispatch('groups/curationReviewProtocolUpdate', {uuid, expertPanel});
        }, 5000);
    },
    async mounted () {
        await this.$store.dispatch('groups/findAndSetCurrent', this.uuid);
        await this.getAnnualReview();
    }
}
</script>
<template>
    <div class="annual-review relative">
        <static-alert :variant="dueDateAlertVariant" class="mb-4" v-if="!annualReview.completed_at">
            The annual review for {{window.for_year}}
            is due on {{formatDate(window.end)}}
        </static-alert>
        <router-link class="note" :to="{name: 'GroupList'}">Groups</router-link>
        <span class="note"> > </span>
        <router-link v-if="group.uuid" class="note" :to="groupDetailRoute">
                {{group.displayName}}
        </router-link>

        <h1>
            {{group.displayName}} - Annual Review for {{year}}
            <note class="font-normal">
                Group ID: {{group.id}}
                |
                ExpertPanel ID: {{group.expert_panel.id}}
                |
                AnnualReview ID: {{annualReview.id}}
                |
                Last Saved: {{formatDateTime(lastSaved)}}
            </note>
        </h1>

        <static-alert variant="success" class="mb-4" v-if="annualReview.completed_at">
            Your annual review was submitted on {{formatDate(annualReview.completed_at)}}
        </static-alert>

            <submitter-information v-model="annualReview" :errors="errors" />

            <transition name="slide-fade-down">
                <div v-if="expertPanel.is_vcep || (expertPanel.is_gcep && annualReview.data.ep_activity == 'active') ">
                    <membership-update v-model="annualReview" :errors="errors" />

                    <template v-if="expertPanel.is_gcep">
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

                    <app-section v-if="expertPanel.is_vcep" title="Use of Variant Curation Interface (VCI)">
                        <vci-use v-model="annualReview" :errors="errors"></vci-use>
                    </app-section>

                    <app-section title="Goals for next year">
                        <goals-form v-model="annualReview" :errors="errors" />
                    </app-section>

                    <app-section title="Additional Funding">
                        <funding-form v-model="annualReview" :errors="errors" />
                    </app-section>

                    <website-attestation v-model="annualReview" :errors="errors" />
                    
                    <template 
                        v-if="expertPanel.is_vcep && expertPanel.has_approved_draft">
                        <!-- <dev-component>Begin questions for specifcation-ed VCEPS</dev-component> -->
                        <app-section title="Progress on Rule Specification">
                            <specification-progress v-model="annualReview" :errors="errors" />
                        </app-section>

                        <app-section title="Summary of total number of variants curated">
                            <vcep-totals v-model="annualReview" :errors="errors" />
                        </app-section>
                        <!-- <dev-component>End questions for specifcation-ed VCEPS</dev-component> -->
                    </template>

                    <template v-if="expertPanel.is_vcep && expertPanel.sustained_curation_is_approved">
                        <!-- <dev-component>Begin Questions for sustained curation</dev-component> -->
                        
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

                        <!-- <dev-component>End Questions for sustained curation</dev-component> -->
                    </template>

                </div>
            </transition>
            <hr>
            <button class="btn btn-lg" @click="submit" v-if="!annualReview.completed_at">Submit annual update</button>
            <static-alert variant="danger mt-4" v-if="hasErrors">
                There are problems with your annual review that must be corrected before you can submit.  
                <br>
                Please see items highlighted in red above.
            </static-alert>

            <static-alert variant="success" v-if="annualReview.completed_at">
                Thank you for submitting your annual review.
            </static-alert>

        <br>
        <br>

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
