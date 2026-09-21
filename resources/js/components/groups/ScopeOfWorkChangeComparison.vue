<script setup>
import { computed } from 'vue'
import InlineTextDiff from './InlineTextDiff.vue'

const props = defineProps({ comparison: { type: Object, required: true } })
const labels = {
  'group.name': 'Group name', 'group.description': 'Group description',
  'expert_panel.long_base_name': 'Panel long name', 'expert_panel.short_base_name': 'Panel short name',
  scope_description: 'Scope description', membership_description: 'Membership description',
  genes: 'Genes', members: 'Members',
}
const textChanges = computed(() => (props.comparison.changes ?? []).filter(change => Object.hasOwn(labels, change.section) && !['genes', 'members'].includes(change.section)))
const unavailable = computed(() => props.comparison.unavailable_sections ?? [])
const unavailableLabel = key => labels[key] ?? (key.startsWith('member_roles:') ? `Historical roles for member ${key.split(':')[1]}` : key)
const operationLabel = operation => ({ added: 'Added', removed: 'Removed', changed: 'Changed' })[operation]
const rowClass = operation => ({ 'text-red-700': operation === 'removed', 'text-green-800': operation === 'added' })
const value = row => row.after ?? row.before
</script>

<template>
  <div class="space-y-4">
    <header>
      <h3 class="font-semibold">Submitted Scope of Work comparison</h3>
      <p class="text-gray-600">
        {{ comparison.mode === 'approved_baseline' ? 'Approved baseline' : `Previous submission #${comparison.before?.submission_id}` }}
        ? Submission #{{ comparison.after?.submission_id }}
      </p>
      <p class="text-gray-600">This shows submitted snapshots; unsubmitted edits are not included.</p>
      <p>{{ comparison.summary?.changed_items ?? 0 }} recorded change(s){{ comparison.status === 'partial' ? ' in available sections' : '' }}. All available genes and members are shown.</p>
      <p class="text-xs">Added text is highlighted and underlined; removed text is red and struck through.</p>
    </header>
    <div v-if="unavailable.length" class="rounded border border-yellow-300 bg-yellow-50 p-3" role="status">
      <strong>{{ !comparison.before?.snapshot_id || !comparison.after?.snapshot_id ? 'Historical snapshot unavailable.' : 'Partial comparison.' }}</strong>
      <p>These sections cannot be compared because historical details are unavailable:</p>
      <ul class="list-disc pl-5"><li v-for="section in unavailable" :key="section">{{ unavailableLabel(section) }}</li></ul>
    </div>
    <div v-for="change in textChanges" :key="change.key">
      <h4 class="font-semibold">{{ labels[change.section] }}</h4>
      <InlineTextDiff :before="change.before ?? ''" :after="change.after ?? ''" />
    </div>
    <section v-for="section in ['genes', 'members']" :key="section">
      <h4 class="font-semibold">{{ labels[section] }}</h4>
      <p v-if="comparison.rows?.[section] == null" class="text-gray-600">Historical {{ section }} are unavailable for comparison.</p>
      <p v-else-if="!comparison.rows[section].length" class="text-gray-600">No {{ section }} in either snapshot.</p>
      <ul v-else class="divide-y">
        <li v-for="row in comparison.rows[section]" :key="row.key" class="py-2">
          <div :class="rowClass(row.operation)">
            <span :class="{ 'line-through': row.operation === 'removed' }">{{ row.label || value(row)?.label || row.key }}</span>
            <span v-if="operationLabel(row.operation)" class="ml-2 rounded border px-1 text-xs font-semibold">{{ operationLabel(row.operation) }}</span>
          </div>
          <p v-if="row.operation === 'changed' && row.before?.label !== row.after?.label" class="text-xs">Before: {{ row.before?.label }} ? After: {{ row.after?.label }}</p>
          <template v-if="section === 'members'">
            <p v-if="row.roles_available === false" class="text-xs text-gray-600">Historical role details are unavailable.</p>
            <ul v-else-if="row.roles?.length" class="ml-4 flex flex-wrap gap-2 text-xs" aria-label="Member roles">
              <li v-for="role in row.roles" :key="role.key" :class="rowClass(role.operation)">
                <span :class="{ 'line-through': role.operation === 'removed' }">{{ role.label || value(role)?.label || role.key }}</span>
                <span v-if="operationLabel(role.operation)" class="ml-1 rounded border px-1">{{ operationLabel(role.operation) }}</span>
              </li>
            </ul>
            <p v-else class="text-xs text-gray-600">No roles.</p>
          </template>
        </li>
      </ul>
    </section>
  </div>
</template>
