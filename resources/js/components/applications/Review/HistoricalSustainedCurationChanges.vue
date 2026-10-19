<script setup>
import { computed } from 'vue'
import HistoricalEvidenceSummaryChange from './HistoricalEvidenceSummaryChange.vue'
import HistoricalSustainedCuration from './HistoricalSustainedCuration.vue'
import { sustainedCurationChanges } from './sustained_curation_changes'
const props = defineProps({ change: { type: Object, required: true } })
const isEvidence = computed(() => /evidence[ _]summaries/i.test(props.change.label ?? props.change.key ?? props.change.section ?? ''))
const rows = computed(() => sustainedCurationChanges(props.change.before, props.change.after))
const section = value => ({ key: props.change.key ?? props.change.section, label: props.change.label, available: true, value })
</script>

<template>
  <div v-if="rows !== null" class="space-y-3">
    <div v-for="(row, index) in rows" :key="index" class="rounded border p-3" :class="row.operation === 'Added' ? 'border-blue-600 bg-blue-50' : row.operation === 'Removed' ? 'border-orange-600 bg-orange-50' : 'border-gray-300'">
      <strong class="mb-2 block">{{ row.operation }}</strong>
      <HistoricalEvidenceSummaryChange v-if="row.operation === 'Changed' && isEvidence" :before="row.before" :after="row.after" />
      <div v-else-if="row.operation === 'Changed'" class="grid gap-3 md:grid-cols-2">
        <div><strong>Previous</strong><HistoricalSustainedCuration :section="section([row.before])" :show-heading="false" /></div>
        <div><strong>Submitted</strong><HistoricalSustainedCuration :section="section([row.after])" :show-heading="false" /></div>
      </div>
      <HistoricalSustainedCuration v-else :section="section([row.after ?? row.before])" :show-heading="false" />
    </div>
  </div>
  <!-- A missing captured value must not become an empty list of removals/additions. -->
  <div v-else class="grid gap-3 md:grid-cols-2">
    <div><strong>Previous</strong><HistoricalSustainedCuration :section="section(change.before)" :show-heading="false" /></div>
    <div><strong>Submitted</strong><HistoricalSustainedCuration :section="section(change.after)" :show-heading="false" /></div>
  </div>
</template>
