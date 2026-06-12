<script setup>
    import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList.vue'
    import {computed, watch } from 'vue'
    import {useStore} from 'vuex'
    import ReviewSection from '@/components/expert_panels/ReviewSection.vue'

    const store = useStore();
    const group = computed(() => store.getters['groups/currentItemOrNew'])
    const expertPanel = computed(() => group.value.expert_panel)

    watch(() => group.value, (to, from) => {
        if ((to.id && (!from || to.id !== from.id))) {
            store.dispatch('groups/getEvidenceSummaries', group.value);
        }
    });
</script>

<template>
  <div class="application-review p-2 bg-gray-100">
    <ReviewSection
      v-if="expertPanel.has_approved_pilot"
      title="Plans for Ongoing Review and Discrepancy Resolution"
      name="discrepency-review"
    >
      <dictionary-row label="Selected protocol" label-width-class="w-48 font-bold">
        <div class="w-full">
          {{ expertPanel.curation_review_protocol ? titleCase(expertPanel.curation_review_protocol.full_name) : null }}
          <p v-if="expertPanel.curation_review_protocol_id == 100" class="mt-1">
            <em>Details:</em> {{ expertPanel.curation_review_protocol_other }}
          </p>
        </div>
      </dictionary-row>
      <dictionary-row label="Notes" label-width-class="w-48 font-bold">
        <markdown-block :markdown="expertPanel.curation_review_process_notes" />
      </dictionary-row>
    </ReviewSection>

    <ReviewSection
      v-if="expertPanel.has_approved_pilot"
      title="Evidence Summaries"
      name="evidence-summaries"
    >
      <EvidenceSummaryList :readonly="true" />
    </ReviewSection>

    <ReviewSection
      v-if="expertPanel.has_approved_pilot"
      title="Core Approval Member, Trained Biocurator, and Biocurator Trainer Designation"
      name="member-designation"
    >
      <dictionary-row label="Core Approval Members" label-width-class="w-48 font-bold">
        <div class="space-y-1 w-full">
          <div
            v-for="member in group.coreApprovalMembers"
            :key="member.id"
            class="flex flex-col gap-2 border-b border-gray-200 pb-1 last:border-b-0 last:pb-0 md:flex-row md:items-center md:justify-between"
          >
            <div>{{ member.person.name }}</div>
            <div class="flex flex-wrap items-center gap-2">
              <template v-if="member.person?.core_member_attestation_completed">
                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Attestation Completed</span>
                <span class="text-sm text-gray-500">{{ formatDate(member.person?.core_member_attestation_completion_date) }}</span>
              </template>
              <span v-else class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800">Attestation Required</span>
            </div>
          </div>
        </div>
      </dictionary-row>
      <dictionary-row label="Biocurator Trainers" label-width-class="w-48 font-bold">
        {{ group.biocuratorTrainers.map(m => m.person.name).join(', ') }}
      </dictionary-row>
      <dictionary-row label="Trained Biocurators" label-width-class="w-48 font-bold">
        {{ group.trainedBiocurators.map(m => m.person.name).join(', ') }}
      </dictionary-row>
    </ReviewSection>

    <!-- <div v-if="!expertPanel.has_appoved_pilot" class="screen-block">
                No approved pilot.
            </div> -->
  </div>
</template>
