<script>
import { debounce } from 'lodash-es'
import { api, isValidationError } from '@/http'

// Common Components
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import SubmitterInformation from '@/components/annual_update/SubmitterInformation.vue'
import GoalsForm from '@/components/annual_update/GoalsForm.vue'
import FundingForm from '@/components/annual_update/FundingForm.vue'
import MemberDesignationUpdate from '@/components/annual_update/MemberDesignationUpdate.vue'
import MembershipUpdate from '@/components/annual_update/MembershipUpdate.vue'

// GCEP Components
import GciGtUse from '@/components/annual_update/GciGtUse.vue'
import GcepRereviewForm from '@/components/annual_update/GcepRereviewForm.vue'
import GcepOngoingPlansUpdateForm from '@/components/annual_update/GcepOngoingPlansUpdateForm.vue'
import GeneCurationTotals from '@/components/annual_update/GeneCurationTotals.vue'

// VCEP Components
// import VariantCurationWorkflow from '@/components/annual_update/VariantCurationWorkflow.vue'
import VciUse from '@/components/annual_update/VciUse.vue'
import VcepOngoingPlansUpdateForm from '@/components/annual_update/VcepOngoingPlansUpdateForm.vue'
// import VcepPlansForSpecifications from '@/components/annual_update/VcepPlansForSpecifications.vue'
// import VcepTotals from '@/components/annual_update/VcepTotals.vue'
import VariantReanalysis from '@/components/annual_update/VariantReanalysis.vue'
import SpecificationProgress from '@/components/annual_update/SpecificationProgress.vue'

export default {
    name: 'AnnualUpdateForm',
    components: {
        AppSection: ApplicationSection,
        SubmitterInformation,
        GciGtUse,
        // SystemIssues,
        GeneCurationTotals,
        VcepOngoingPlansUpdateForm,
        GcepRereviewForm,
        GoalsForm,
        FundingForm,
        GcepOngoingPlansUpdateForm,
        VciUse,
        SpecificationProgress,
        // VcepTotals,
        VariantReanalysis,
        MemberDesignationUpdate,
        // VcepPlansForSpecifications,
        // VariantCurationWorkflow,
        MembershipUpdate,
    },
    props: {
        uuid: {
            required: true,
            type: String
        },
        id: {
            required: false,
        }
    },
    data() {
        return {
            annualUpdate: {
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
                    // SubmitterInformation
                    // submitter_id is in top level
                    grant: null,
                    ep_activity: null,
                    submitted_inactive_form: null,
                    // MembershipUpdate
                    membership_attestation: null,
                    expert_panels_change: null,
                    // GciGtUse (GCEP)
                    gci_use: null,
                    gci_use_details: null,
                    gt_gene_list: null,
                    gt_gene_list_details: null,
                    gt_precuration_info: null,
                    gt_precuration_info_details: null,
                    // GeneCurationTotals (GCEP)
                    published_count: null,
                    approved_unpublished_count: null,
                    in_progress_count: null,
                    publishing_issues: null,
                    // GcepOngoingPlansUpdateForm (GCEP)
                    // ongoing_plans_updated: null, // same var used in VCEP form, leaving here as comment to show it's also in a GCEP form
                    // ongoing_plans_update_details: null, // same var used in VCEP form
                    // GcepOngoingPlansForm (GCEP) appears to interact directly through `group`?
                    // GcepRereviewForm (GCEP)
                    recuration_begun: null,
                    recuration_designees: null,
                    // VciUse (VCEP)
                    vci_use: null,
                    vci_use_details: null,
                    // GoalsForm
                    goals: null,
                    cochair_commitment: null,
                    cochair_commitment_details: null,
                    long_term_chairs: null,
                    // FundingForm
                    applied_for_funding: null,
                    funding: null,
                    funding_other_details: null,
                    funding_thoughts: null,
                    // WebsiteAttestation
                    website_attestation: null,
                    // SpecificationProgress (VCEP with approved draft)
                    specifications_for_new_gene: null,
                    specifications_for_new_gene_details: null,
                    submit_clinvar: null,
                    vcep_publishing_issues: null,
                    system_discrepancies: null,
                    // VariantReanalysis (VCEP with sustained curation)
                    rereview_discrepencies_progress: null,
                    // VcepOngoingPlansUpdateForm (VCEP with sustained curation)
                    ongoing_plans_updated: null,
                    ongoing_plans_update_details: null,
                    changes_to_call_frequency: null,
                    changes_to_call_frequency_details: null,
                    // MemberDesignationUpdate (VCEP with sustained curation)
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
        showLastYearLink () {
            return this.expertPanel &&
                this.expertPanel.previous_year_annual_update &&
                this.annualUpdate &&
                this.expertPanel.previous_year_annual_update.id !== this.annualUpdate.id
        },
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        year () {
            const thisYear = this.annualUpdate.window ? this.annualUpdate.window.for_year : (new Date()).getFullYear()-1;
            return thisYear;
        },
        hasErrors () {
            return Object.keys(this.errors).length > 0;
        },
        window () {
            return this.annualUpdate.window || {}
        },
        dueDateAlertVariant () {
            return 'info';
        },
        expertPanel () {
            return this.annualUpdate.expert_panel || {};
        },
        groupDetailRoute () {
            return {name: 'GroupDetail', params: {uuid: this.group.uuid}}
        }
    },
    watch: {
        annualUpdate: {
            handler () {
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
                const updatedAnnualUpdate = await api.post(`/api/groups/${this.group.uuid}/expert-panel/annual-updates/${this.annualUpdate.id}`)
                    .then(response => response.data);
                this.annualUpdate.completed_at = updatedAnnualUpdate.completed_at;
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
            this.$router.replace({name: 'AnnualUpdate', params: {uuid: this.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'AnnualUpdate', params: {uuid: this.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
        async getAnnualUpdate () {
            // eslint-disable-next-line no-console
            console.log('AnnualUpdateForm.getAnnualUpdate', this.$route.params.id)
            let url = `/api/groups/${this.group.uuid}/expert-panel/annual-updates`;
            if (this.id) {
                url += `/${this.id}`
            }
            // eslint-disable-next-line no-console
            console.log(url);
            this.annualUpdate = await api.get(url)
                .then(response => {
                    const mergedData = {...this.annualUpdate.data, ...response.data.data}
                    const reviewData = response.data;
                    reviewData.data = mergedData;
                    return reviewData;
                });
            this.lastSaved = new Date(Date.parse(this.annualUpdate.updated_at));
        },
        async save() {
            if (!this.annualUpdate.id)  return;
            try {
                this.saving = true;
                await api.put( `/api/groups/${this.group.uuid}/expert-panel/annual-updates/${this.annualUpdate.id}`,  this.annualUpdate);
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
        }, 2000);
    },
    async mounted () {
        await this.$store.dispatch('groups/findAndSetCurrent', this.uuid);
        await this.getAnnualUpdate();
    }
}
</script>
<template>
    <div class="annual-update relative">
        <static-alert :variant="dueDateAlertVariant" class="mb-4" v-if="!annualUpdate.completed_at">
            This annual update for {{ window.for_year }} is due on {{ formatDate(window.end) }}
        </static-alert>
        <static-alert v-if="showLastYearLink" class="mb-4">
            Refer to <a :href="`/annual-updates/${expertPanel.previous_year_annual_update.id}`" class="font-bold" target="ann-up-responses">your responses from last year</a>.
        </static-alert>
        <group-breadcrumbs :group="group" />

        <h1>
            {{ group.displayName }} - Annual Update for {{ year }}
            <note class="font-normal">
                Group ID: {{ group.id }}
                |
                ExpertPanel ID: {{ group.expert_panel.id }}
                |
                AnnualUpdate ID: {{ annualUpdate.id }}
                |
                Last Saved: {{ formatDateTime(lastSaved) }}
            </note>
        </h1>

        <static-alert v-if="annualUpdate.completed_at" variant="success" class="mb-4">
            Your annual update was submitted on {{ formatDate(annualUpdate.completed_at) }}
        </static-alert>

            <SubmitterInformation v-model="annualUpdate" :errors="errors" />

                <!-- <div v-if="group.is_vcep || (group.is_gcep && annualUpdate.data.ep_activity == 'active') "> -->
                <div>
                    <MembershipUpdate v-model="annualUpdate" :errors="errors" />

                    <template v-if="expertPanel.is_gcep">
                        <AppSection title="Use of GCI and GeneTracker Systems">
                            <GciGtUse v-model="annualUpdate" :errors="errors" />
                        </AppSection>

                        <AppSection title="Summary of total numbers of genes curated">
                            <GeneCurationTotals v-model="annualUpdate" :errors="errors" />
                        </AppSection>

                        <AppSection title="Changes to plans for ongoing curation">
                            <GcepOngoingPlansUpdateForm v-model="annualUpdate" :errors="errors" @updated="saveOngoingPlans"></GcepOngoingPlansUpdateForm>
                        </AppSection>

                        <AppSection title="Gene Re-curation/Re-review">
                            <GcepRereviewForm v-model="annualUpdate" :errors="errors"></GcepRereviewForm>
                        </AppSection>
                    </template>

                    <AppSection v-if="expertPanel.is_vcep" title="Use of Variant Curation Interface (VCI)">
                        <VciUse v-model="annualUpdate" :errors="errors"></VciUse>
                    </AppSection>

                    <AppSection title="Goals for next year">
                        <GoalsForm v-model="annualUpdate" :errors="errors" />
                    </AppSection>

                    <AppSection title="Additional Funding">
                        <FundingForm v-model="annualUpdate" :errors="errors" />
                    </AppSection>

                    <website-attestation v-model="annualUpdate" :errors="errors" />

                    <template
                        v-if="expertPanel.is_vcep && expertPanel.has_approved_draft">
                        <!-- <dev-component>Begin questions for specifcation-ed VCEPS</dev-component> -->
                        <AppSection title="Progress on Rule Specification">
                            <SpecificationProgress v-model="annualUpdate" :errors="errors" />
                        </AppSection>

                    </template>

                    <template v-if="expertPanel.is_vcep && expertPanel.sustained_curation_is_approved">
                        <!-- <dev-component>Begin Questions for sustained curation</dev-component> -->

                        <!-- <variant-curation-workflow v-model="annualUpdate" :errors="errors" /> -->

                        <VariantReanalysis v-model="annualUpdate" :errors="errors" />

                        <VcepOngoingPlansUpdateForm v-model="annualUpdate" :errors="errors" @updated="saveOngoingPlans"/>

                        <MemberDesignationUpdate
                            v-model="annualUpdate"
                            :errors="errors"
                            @updated="debounceSave"
                        />

                        <!-- <vcep-plans-for-specifications
                            v-model="annualUpdate"
                            :errors="errors"
                        /> -->

                        <!-- <dev-component>End Questions for sustained curation</dev-component> -->
                    </template>

                </div>
            <hr>
            <button class="btn btn-lg" @click="submit" v-if="!annualUpdate.completed_at">Submit annual update</button>
            <static-alert variant="danger mt-4" v-if="hasErrors">
                There are problems with your annual update that must be corrected before you can submit.
                <br>
                Please see items highlighted in red above.
            </static-alert>

            <static-alert variant="success" v-if="annualUpdate.completed_at">
                Thank you for submitting your annual update.
            </static-alert>

        <br>
        <br>

        <teleport to='body'>
            <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="$route.meta.title">
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
        </teleport>
    </div>
</template>
<style lang="postcss">
    .annual-update .input-row .label-container{
        font-size: 1.05em;
        margin-bottom: .5rem;
    }
    .annual-update .application-section {
        padding-bottom: .25rem;
    }
</style>
