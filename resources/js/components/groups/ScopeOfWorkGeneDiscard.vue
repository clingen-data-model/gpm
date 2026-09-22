<script setup>
import { computed, inject, unref } from 'vue'
const props = defineProps({ geneId: [Number, String], field: { type: String, default: null } })
const context = inject('scopeOfWorkGeneDiscard', null)
const revision = computed(() => unref(context?.status)?.active_revision)
const change = computed(() => {
  if (!['draft', 'revisions_requested'].includes(revision.value?.status)) return null
  return revision.value.changes?.find(change => {
    if (!change.can_discard || String(change.after_value?.id ?? change.before_value?.id) !== String(props.geneId)) return false
    return props.field
      ? ['gene.update', 'gene.update_tier'].includes(change.rule_key) && change.field_name === props.field
      : ['gene.add', 'gene.remove'].includes(change.rule_key)
  })
})
const discard = () => {
  if (!change.value || unref(context?.busy)) return
  context.discard({ revision: revision.value, changeId: change.value.id, geneLabel: change.value.entity_label })
}
</script>

<template>
  <button v-if="change" type="button" class="ml-2 rounded border px-2 py-0.5 text-xs"
    :disabled="unref(context?.busy)" :aria-label="`Discard ${field || 'gene'} change`" @click="discard">Discard</button>
</template>
