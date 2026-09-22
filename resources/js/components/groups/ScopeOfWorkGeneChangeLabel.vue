<script setup>
import { computed } from 'vue'
import ScopeOfWorkGeneDiscard from './ScopeOfWorkGeneDiscard.vue'
const props = defineProps({
  comparison: { type: Object, default: null },
  geneId: [Number, String],
  tierLabel: { type: Function, required: true },
  field: { type: String, default: null },
})
const labels = { gene_symbol: 'Gene', hgnc_id: 'HGNC ID', mondo_id: 'Disease ID',
  disease_name: 'Disease', disease_entity: 'Disease entity', moi: 'MOI', tier: 'Tier',
  plan: 'Plan', date_approved: 'Approval date', gt_curation_uuid: 'Curation ID' }
const changes = computed(() => (props.comparison?.field_changes ?? []).filter(change =>
  props.field ? change.field === props.field : change.field !== 'tier'))
const format = (field, value) => field === 'tier' ? props.tierLabel(value)
  : value == null ? '—' : typeof value === 'object' ? JSON.stringify(value) : String(value)
</script>

<template>
  <span v-if="!field && comparison?.operation === 'added'" class="text-xs rounded px-2 py-0.5 bg-green-50 text-green-800">Added</span>
  <span v-else-if="!field && comparison?.operation === 'removed'" class="text-xs rounded px-2 py-0.5 bg-red-50 text-red-700">Removed</span>
  <ScopeOfWorkGeneDiscard v-if="!field && comparison && comparison.operation !== 'unchanged'" :gene-id="geneId" />
  <span v-if="comparison?.operation === 'changed' && changes.length" class="text-xs">
    <span v-for="change in changes" :key="change.field" class="block whitespace-pre-wrap break-words">
      {{ labels[change.field] ?? change.field }}:
      <del class="text-red-700">{{ format(change.field, change.before) }}</del>
      <span aria-hidden="true"> → </span>
      <ins class="text-green-800">{{ format(change.field, change.after) }}</ins>
      <ScopeOfWorkGeneDiscard :gene-id="geneId" :field="change.field" />
    </span>
  </span>
</template>
