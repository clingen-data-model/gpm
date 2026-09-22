<script setup>
import { computed, inject, unref } from 'vue'

const props = defineProps({
  personId: [String, Number],
  kind: { type: String, default: 'member' },
  operation: String,
  roleName: String,
  roleLabel: String,
  memberLabel: String,
})
const context = inject('scopeOfWorkMemberDiscard', null)
const revision = computed(() => unref(context?.status)?.active_revision)
const change = computed(() => {
  if (!['draft', 'revisions_requested'].includes(revision.value?.status)) return null
  return revision.value.changes?.find(change => {
    const value = change.after_value ?? change.before_value
    if (!change.can_discard || props.personId == null || String(value?.person_id) !== String(props.personId)) return false
    if (props.kind === 'member') return change.rule_key === (props.operation === 'added' ? 'member.add' : props.operation === 'removed' ? 'member.remove' : '')
    if (props.kind === 'retirement') return change.rule_key === `member.${props.operation}`
    return props.kind === 'role' && ['member.update_role', 'member.add_chair', 'member.remove_chair'].includes(change.rule_key)
      && value.role === props.roleName && (change.after_value ? 'added' : 'removed') === props.operation
  })
})
const discard = () => {
  if (!change.value || unref(context?.busy)) return
  context.discard({ revision: revision.value, changeId: change.value.id, memberChange: {
    kind: props.kind, label: props.memberLabel || change.value.entity_label,
    roleLabel: props.roleLabel, operation: props.operation,
  } })
}
</script>

<template>
  <button v-if="change" type="button" class="ml-2 rounded border px-2 py-0.5 text-xs"
    :disabled="unref(context?.busy)" :aria-label="`Discard ${kind === 'role' ? roleLabel : kind} change`"
    @click.stop="discard">Discard</button>
</template>
