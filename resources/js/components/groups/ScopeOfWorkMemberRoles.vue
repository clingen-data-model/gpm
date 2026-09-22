<script setup>
import { computed } from 'vue'
import { titleCase } from '@/string_utils'
import ScopeOfWorkMemberDiscard from './ScopeOfWorkMemberDiscard.vue'

const props = defineProps({
  roles: { type: Array, default: () => [] },
  comparison: { type: Object, default: null },
  snapshotOnly: Boolean,
  preview: Boolean,
  allowDiscard: Boolean,
  personId: [String, Number],
  memberLabel: String,
})
const contextual = computed(() => props.comparison?.roles_available
  && props.comparison?.roles?.some(role => role.operation !== 'unchanged')
  && !['added', 'removed'].includes(props.comparison.operation))
const ordinaryLabels = computed(() => props.roles.map(role => props.snapshotOnly
  ? role.label : props.preview ? titleCase(role.name) : role.display_name).join(', '))
</script>

<template>
  <span v-if="!contextual">{{ ordinaryLabels || (preview ? '--' : '') }}</span>
  <span v-else class="inline-flex flex-wrap gap-x-2 gap-y-1">
    <span v-for="role in comparison.roles" :key="role.key">
      <del v-if="role.operation === 'removed'" class="text-red-700">{{ role.before.label }}</del>
      <ins v-else-if="role.operation === 'added'" class="text-green-800 no-underline">{{ role.after.label }}</ins>
      <span v-else>{{ role.after?.label ?? role.before?.label }}</span>
      <span v-if="role.operation === 'added'" class="ml-1 text-xs text-green-800">Added</span>
      <span v-if="role.operation === 'removed'" class="ml-1 text-xs text-red-700">Removed</span>
      <ScopeOfWorkMemberDiscard v-if="allowDiscard && ['added', 'removed'].includes(role.operation)"
        :person-id="personId" kind="role" :operation="role.operation" :role-name="role.key"
        :role-label="role.after?.label ?? role.before?.label" :member-label="memberLabel" />
    </span>
  </span>
</template>
