<script>
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm.vue'
import EvidenceSummaries from '@/components/expert_panels/EvidenceSummaryList.vue'
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm.vue'
import {debounce} from 'lodash'

export default {
    name: 'SustainedCurationReview',
    components: {
        VcepOngoingPlansForm,
        EvidenceSummaries,
        MemberDesignationForm,
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            errors: {},
            attestation: false
        }
    },
    computed: {
        group () {
            return  this.$store.getters['groups/currentItemOrNew'];
        },
        expertPanel () {
            return this.group.expert_panel;
        },
        canSubmit () {
            return !this.attestation
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler () {
                this.getGroup();
            }
        }
    },
    methods: {
        getGroup () {
            this.$store.dispatch('groups/findAndSetCurrent', this.uuid);
        },
        async submitReview () {
            await this.$store.dispatch('groups/completeSustainedCurationReview', this.group);
            this.$router.go(-1);
        }
    },
    created () {
        this.saveOngoingPlans = debounce(() => {
            const {uuid, expert_panel: expertPanel} = this.group;
            return this.$store.dispatch('groups/curationReviewProtocolUpdate', {uuid, expertPanel});
        }, 2000);
    }
}
</script>
<template>
    <div class="mb-8">
        <group-breadcrumbs :group="group" />

        <h1>Review Sustained Curation Details for {{group.displayName}}</h1>

        <p class="text-md-lg">Each time a new ACMG Guidelines Specification is approved we ask that your review your sustained curation information and make sure it's up to date and accurate.</p>

        <section>
            <header>
                <h3>Plans for Ongoing Review and Reanalysis and Discrepancy Resolution</h3>
            </header>
            <vcep-ongoing-plans-form
                @update="saveOngoingPlans"
                :errors="errors"
            />
        </section>

        <section>
            <header>  
                <h3>Example Evidence Summaries</h3>
            </header>
            <evidence-summaries 
                :group="group" 
                class="pb-2 mb-4 border-b" 
            />
        </section>

        <section>
            <header>  
                <h3>Example Evidence Summaries</h3>
            </header>
            <member-designation-form 
                class="pb-2 mb-4 border-b" 
            />
        </section>

        <section>
            <header>
                <h3>Attestion of Accuracy</h3>
            </header>
            <checkbox 
                label="I confirm that all of the information is is accurate and up to date." 
                v-model="attestation" 
                class="text-md-lg"
            />
            <button 
                @click="submitReview"
                :disabled="canSubmit"
                class="btn btn-lg blue" 
            >
                Submit
            </button>
        </section>
    </div>
</template>
<style lang="postcss" scoped>
    section {
        @apply bg-white p-4 border-b mb-8
    }
</style>