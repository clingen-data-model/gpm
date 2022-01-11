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
        <app-section title="Membership">
            <p>
                 Please list the entire membership of the Expert Panel and indicate which, if any, members have been added or removed. 
                 
                 <note>If changes are made to an Expert Panel Co-chair(s) or coordinator, please report them directly to the <a href="cdwg_oversightcommittee@clinicalgenome.org">Clinical Domain Working Group Oversight Committee</a> when they occur.</note>
            </p>
            <member-list />
        </app-section>

        <template v-if="group.isGcep()">
            <app-section title="Use of GCI and GeneTracker Systems">
                <gci-gt-use v-model="annualReview" :errors="errors" />
            </app-section>
            <app-section title="Summary of total numbers of genes curated">
                <gene-curation-totals v-model="annualReview" :errors="errors" />
            </app-section>
            <app-section title="Changes to plans for ongoing curation">
                <gcep-ongoing-plans-form 
                    v-model="annualReview"
                    :errors="errors"
                    @updated="saveOngoingPlans"
                />
            </app-section>
            <app-section title="Gene Re-curation/Re-review">
                <gcep-rereview-form v-model="annualReview" :errors="errors"></gcep-rereview-form>
            </app-section>
            <app-section title="Goals for next year">
                <input-row label="Describe the Expert Panel’s plans and goals for the next year. Also, please indicate if the co-chairs plan to continue leading the EP for the next year." :errors="errors.goals" vertical>
                    <textarea v-model="annualReview.goals" rows="5" class="w-full"></textarea>
                </input-row>
            </app-section>
        </template>

        <template v-if="group.isVcep()">

        </template>

        <app-section title="Additional Funding">
            <input-row label="Please describe any thoughts, ideas, or plans for soliciting funding or personnel (in addition to any existing funding/support you may have)." :errors="errors.additional_funding" vertical>
                <textarea v-model="annualReview.additional_funding" rows="5" class="w-full"></textarea>
            </input-row>
        </app-section>

        <app-section title="Webpage Updates">
            <input-row :errors="errors.website_attestation" vertical>
                <template v-slot:label>
                    <p>
                        Please review your ClinGen EP webpage, including Expert Panel Status, description, membership, COI and relevant document including publications. Please contact <a href="mailto:dazzarit@broadinstitute.org">Danielle Azzariti</a> with any questions."
                    </p>
                </template>
                <checkbox 
                    label="I attest that the information on the webpage is up-to-date and accurate." 
                    v-model="annualReview.website_attestation"
                />
            </input-row>
            <hr>
            <button class="btn btn-lg" @click="submit">Submit annual update</button>
            <br>
        </app-section>

        <template v-if="group.isVcep() && group.expert_panel.defIsApproved">
            
        </template>

        <template v-if="group.isVcep() && group.expert_panel.pilotSpecificationsIsApproved">

        </template>

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
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm'
import VcepRereviewForm from '@/components/annual_review/VcepRereviewForm'
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm'
import GcepRereviewForm from '@/components/annual_review/GcepRereviewForm'

export default {
    name: 'AnnualReviewForm',
    components: {
        'app-section': ApplicationSection,
        SubmitterInformation,
        MemberList,
        GciGtUse,
        GcepOngoingPlansForm,
        GeneCurationTotals,
        VcepOngoingPlansForm,
        GcepRereviewForm,
        VcepRereviewForm,
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
        ongoingCurationPlansForm () {
            return this.group.isVcep() ? VcepOngoingPlansForm : GcepOngoingPlansForm
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