<script setup>
    import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList.vue'
    import { watch } from 'vue'
    import {useStore} from 'vuex'
    import setupReviewData from '@/composables/setup_review_data.js'
    import ReviewSection from '@/components/expert_panels/ReviewSection.vue'

    const store = useStore();
    const {group, expertPanel, members, isGcep, isVcep} = setupReviewData(store);

    watch(() => group.value, (to, from) => {
        if ((to.id && (!from || to.id != from.id))) {
            store.dispatch('groups/getEvidenceSummaries', group.value);
        }
    });
</script>

<template>
    <div class="application-review p-2 bg-gray-100">
            <ReviewSection v-if="expertPanel.has_approved_pilot" title="Plans for Ongoing Review and Descrepency Resolution">
                <dictionary-row label="Selected protocol" labelWidthClass="w-48 font-bold">
                    <div class="w-full">
                        {{expertPanel.curation_review_protocol ? titleCase(expertPanel.curation_review_protocol.full_name) : null}}
                        <p v-if="expertPanel.curation_review_protocol_id == 100" class="mt-1">
                            <em>Details:</em> {{expertPanel.curation_review_protocol_other}}
                        </p>
                    </div>
                </dictionary-row>
                <dictionary-row label="Notes" labelWidthClass="w-48 font-bold">
                    <markdown-block :markdown="expertPanel.curation_review_process_notes" />
                </dictionary-row>
            </ReviewSection>

            <ReviewSection v-if="expertPanel.has_approved_pilot" title="Evidence Summaries">
                <evidence-summary-list :readonly="true" />
            </ReviewSection>

            <ReviewSection v-if="expertPanel.has_approved_pilot" title="Core Approval Member, Trained Biocurator, and Biocurator Trainer Designation">
                <dictionary-row label="Core Approval Members" labelWidthClass="w-48 font-bold">
                    {{this.group.coreApprovalMembers.map(m => m.person.name).join(', ')}}
                </dictionary-row>
                <dictionary-row label="Biocurator Trainers" labelWidthClass="w-48 font-bold">
                    {{this.group.biocuratorTrainers.map(m => m.person.name).join(', ')}}
                </dictionary-row>
                <dictionary-row label="Trained Biocurators" labelWidthClass="w-48 font-bold">
                    {{this.group.trainedBiocurators.map(m => m.person.name).join(', ')}}
                </dictionary-row>
            </ReviewSection>

    </div>
</template>
