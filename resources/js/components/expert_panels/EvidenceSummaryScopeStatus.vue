<script setup>
import { computed } from 'vue'
const props = defineProps({ summary: { type: Object, required: true }, group: { type: Object, required: true } })
const label = computed(() => {
  if (!(props.group.is_vcep || props.group.is_scvcep) || !props.summary.gene?.deleted_at) return ''
  return props.summary.gene_scope_status === 'pending_removal'
    ? 'Pending removal from Scope of Work' : 'Removed from current Scope of Work'
})
</script>
<template>
  <p v-if="label" class="my-2 rounded bg-amber-50 px-2 py-1 text-sm text-amber-900" role="status">{{ label }}</p>
</template>
