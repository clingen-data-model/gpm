<script setup>
import { computed, inject, unref } from 'vue'

defineProps({ variant: { type: String, default: 'compact' } })
const state = inject('scopeOfWorkComparisonState', null)
const comparison = computed(() => unref(state?.comparison))
const labels = {
  'group.name': 'Group name',
  'expert_panel.long_base_name': 'Panel long base name',
  'expert_panel.short_base_name': 'Panel short base name',
}
const change = computed(() => {
  const available = (comparison.value?.changes ?? []).filter(change =>
    Object.prototype.hasOwnProperty.call(labels, change.section)
    && change.before !== change.after
    && !comparison.value?.unavailable_sections?.includes(change.section)
  )
  return available.find(change => change.section === 'group.name')
    ?? available.find(change => change.section === 'expert_panel.long_base_name')
    ?? null
})
const heading = computed(() => comparison.value?.mode === 'approved_baseline'
  ? 'Name changes since approved baseline'
  : comparison.value?.source === 'live'
    ? 'Name changes since last submission'
    : 'Name changes since previous review round')
</script>

<template>
  <span v-if="change && variant === 'inline-title'">
    <del class="text-red-700">{{ change.before || '(not provided)' }}</del>{{ ' ' }}<ins class="text-green-800">{{ change.after || '(not provided)' }}</ins>
  </span>
  <span v-else-if="change" class="inline-block text-sm font-normal" :aria-label="heading">
    <span class="block font-semibold">{{ heading }}</span>
    <span class="block text-xs text-gray-600">{{ comparison?.source === 'live'
      ? 'Current saved draft changes; unsaved edits are not included.'
      : 'Submitted changes only; unsubmitted edits are not included.' }}</span>
    <span class="block text-red-700">
      <span class="font-semibold">Previous: </span>
      <del>{{ change.before || '(not provided)' }}</del>
    </span>
    <span class="block text-green-800">
      <span class="font-semibold">{{ comparison?.source === 'live' ? 'Current: ' : 'Submitted: ' }}</span>
      <ins>{{ change.after || '(not provided)' }}</ins>
    </span>
  </span>
  <slot v-else />
</template>
