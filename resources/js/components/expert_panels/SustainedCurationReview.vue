<script setup>
  import { ref } from 'vue'
  import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList.vue'
  import {computed, watch } from 'vue'
  import {useStore} from 'vuex'
  import ReviewSection from '@/components/expert_panels/ReviewSection.vue'

  const store = useStore();
  const group = computed(() => store.getters['groups/currentItemOrNew'])
  const expertPanel = computed(() => group.value.expert_panel)
  const expandedAttestationResponses = ref({})

  const toggleAttestationResponses = (memberId) => {
    expandedAttestationResponses.value[memberId] = !expandedAttestationResponses.value[memberId]
  }

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
        <div class="space-y-2 w-full">
          <div v-for="member in group.coreApprovalMembers" :key="member.id" class="border-b border-gray-200 pb-2 last:border-b-0 last:pb-0">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="min-w-0 font-medium">{{ member.person.name }}</div>
              <div class="flex w-full flex-wrap items-center gap-2 md:w-auto md:justify-end">
                <template v-if="member.person?.core_member_attestation_completed">
                  <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Attestation Completed</span>
                  <span class="text-sm text-gray-500">{{ formatDate(member.person?.core_member_attestation_completion_date) }}</span>
                  <button type="button" class="text-sm text-blue-600 hover:underline" @click="toggleAttestationResponses(member.id)">
                    {{ expandedAttestationResponses[member.id] ? 'Hide responses' : 'View responses' }}
                  </button>
                </template>
                <span v-else class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800">Attestation Required</span>
              </div>
            </div>

            <div v-if="member.person?.core_member_attestation_completed && expandedAttestationResponses[member.id]" class="mt-3 rounded-md bg-gray-50 p-3">
              <div class="mb-1 text-sm font-medium text-gray-900">Responses</div>
              <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                <li v-for="response in member.person?.core_member_attestation_responses || []" :key="response">{{ response }}</li>
              </ul>
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
