<template>
    <div>
        <router-link class="note"
            :to="{name: 'GroupDetail', params: {uuid: group.uuid}}"
            v-if="group.uuid"
        >
            {{group.displayName}}
        </router-link>

        <h1>{{group.displayName}} - Annual Review for {{year}}</h1>
        <app-section title="Submitter Information">
            <submitter-information v-model="annualReview" :errors="errors" />
        </app-section>

        <transition name="slide-fade-down">
            <div v-if="group.isVcep() || group.isGcep() && annualReview.ep_activity == 'active' ">
                <app-section title="Membership">
                    <p>
                        Please list the entire membership of the Expert Panel.
                    </p>
                    <p v-if="group.isVcep()">
                        Note: If changes are made to an Expert Panel Co-chair(s) or coordinator, please report them directly to the <a href="cdwg_oversightcommittee@clinicalgenome.org">Clinical Domain Working Group Oversight Committee</a> when they occur. All current EP members must complete a Conflict of Interest (COI) survey each year. If all members of your EP have filled out the EPAM generated COI survey, some of the information will be auto populated.
                    </p>

                    <member-list />

                    <input-row 
                        vertical 
                        label="Please attest that your membership is up to date" 
                        :errors="errors.membership_attestation"
                    >
                        <div class="ml-4">
                            <radio-button v-model="annualReview.membership_attestation" value="I have reviewed and made the appropriate updates to membership as needed.">
                                I have reviewed and made the appropriate updates to membership as needed.
                            </radio-button>
                            <radio-button v-model="annualReview.membership_attestation" value="I have reviewed and there are no changes needed.">
                                I have reviewed and there are no changes needed.
                            </radio-button>
                        </div>
                    </input-row>
                </app-section>

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
                    <app-section title="Progress on Rule Specification">
                        <specification-progress v-model="annualReview" :errors="errors" />
                    </app-section>

                    <app-section title="Summary of total number of variants curated">
                        <vcep-totals v-model="annualReview" :errors="errors" />
                    </app-section>
                </template>

                <template v-if="group.isVcep() && group.expert_panel.pilotSpecificationsIsApproved">
                    <app-section title="Changes to plans for variant curation workflow">
                        <vcep-ongoing-plans-update-form v-model="annualReview" :errors="errors"></vcep-ongoing-plans-update-form>
                    </app-section>
                </template>

                <hr>
                <button class="btn btn-lg" @click="submit">Submit annual update</button>
                <br>
            </div>
        </transition>



        <teleport to='body'>
            <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import SubmitterInformation from '@/components/annual_review/SubmitterInformation'
import GciGtUse from '@/components/annual_review/GciGtUse'
import GeneCurationTotals from '@/components/annual_review/GeneCurationTotals'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import MemberList from '@/components/groups/MemberList'
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

export default {
    name: 'AnnualReviewForm',
    components: {
        'app-section': ApplicationSection,
        SubmitterInformation,
        MemberList,
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
                submitter_id: null
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
<style lang="postcss" scoped>
    .application-section {
        padding-bottom: 0;
    }
</style>