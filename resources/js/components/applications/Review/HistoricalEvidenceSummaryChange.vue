<script setup>
import { computed } from 'vue'
import InlineTextDiff from '@/components/groups/InlineTextDiff.vue'
const props = defineProps({ before: { type: Object, required: true }, after: { type: Object, required: true } })
const text = value => value == null || value === '' ? 'Not recorded' : String(value)
const disease = item => [item.mondo_id, item.disease].filter(Boolean).join(' ')
const has = value => value != null && value !== ''
const fields = computed(() => [
  { label: 'Gene', before: props.before.gene, after: props.after.gene },
  { label: 'Disease / MONDO', before: disease(props.before), after: disease(props.after) },
  ...['variant', 'assertion_id', 'moi'].filter(key => has(props.before[key]) || has(props.after[key])).map(key => ({
    label: { variant: 'Variant', assertion_id: 'Assertion ID', moi: 'MOI' }[key], before: props.before[key], after: props.after[key],
  })),
  ...['CIViC', 'VCI'].filter(label => has(props.before[`${label} URL`]) || has(props.after[`${label} URL`])).map(label => ({
    label, link: true, before: props.before[`${label} URL`], after: props.after[`${label} URL`],
  })),
].map(field => ({ ...field, changed: text(field.before) !== text(field.after) })))
const href = value => {
  try {
    const url = new URL(value)
    return ['https:', 'http:'].includes(url.protocol) ? url.href : null
  } catch { return null }
}
</script>

<template>
  <div data-testid="changed-evidence" class="space-y-2">
    <div class="flex flex-wrap items-center gap-2" data-testid="evidence-metadata">
      <div v-for="field in fields.filter(field => !field.link)" :key="field.label" :data-field="field.label"
        class="inline-flex flex-wrap items-baseline gap-x-1"
        :class="field.label === 'Gene' ? 'text-base font-semibold' : 'rounded bg-gray-100 px-2 py-1 text-xs'">
        <span v-if="!['Gene', 'Disease / MONDO'].includes(field.label)">{{ field.label }}:</span>
        <template v-for="(value, index) in field.changed ? [field.before, field.after] : [field.after]" :key="index">
          <span v-if="index" aria-hidden="true">&#8594;</span>
          <component :is="field.changed ? (index ? 'ins' : 'del') : 'span'" :class="field.changed ? (index ? 'text-green-800 bg-green-50' : 'text-red-700 bg-red-50') : ''">
            {{ text(value) }}
          </component>
        </template>
      </div>
    </div>
    <div data-testid="evidence-summary"><strong>Summary:</strong> <InlineTextDiff :before="text(before.summary)" :after="text(after.summary)" /></div>
    <div v-for="field in fields.filter(field => field.link)" :key="field.label" :data-field="field.label" class="flex flex-wrap items-baseline gap-x-1">
      <strong v-if="field.changed">{{ field.label }}:</strong>
      <template v-for="(value, index) in field.changed ? [field.before, field.after] : [field.after]" :key="index">
        <span v-if="index" aria-hidden="true">&#8594;</span>
        <component :is="field.changed ? (index ? 'ins' : 'del') : 'span'" :class="field.changed ? (index ? 'text-green-800 bg-green-50' : 'text-red-700 bg-red-50') : ''">
          <a v-if="href(value)" :href="href(value)" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">View {{ field.label }}</a>
          <template v-else>{{ text(value) }}</template>
        </component>
      </template>
    </div>
  </div>
</template>
