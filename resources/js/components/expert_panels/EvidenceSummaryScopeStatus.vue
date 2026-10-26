<script setup>
import { computed } from 'vue'
const props = defineProps({ summary: { type: Object, required: true }, group: { type: Object, required: true } })
const label = computed(() => {
  if (!(props.group.is_vcep || props.group.is_scvcep) || !props.summary.gene?.deleted_at) return ''
  return props.summary.gene_scope_status === 'pending_removal'
    ? 'Pending removal from Scope of Work' : 'Removed from Scope of Work'
})
</script>
<template>
  <span v-if="label" class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-sm text-red-800" role="status">{{ label }}</span>
</template>
