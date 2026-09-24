<script setup>
import HistoricalValue from './HistoricalValue.vue'
import HistoricalGenes from './HistoricalGenes.vue'
import HistoricalMembers from './HistoricalMembers.vue'
import InlineTextDiff from '@/components/groups/InlineTextDiff.vue'
import { computed } from 'vue'
const props = defineProps({ detail: { type: Object, required: true } })
const label = section => ({ 'group.name': 'Panel name', 'group.description': 'Website description',
  'expert_panel.long_base_name': 'Long name', 'expert_panel.short_base_name': 'Short name',
  scope_description: 'Description of scope', membership_description: 'Membership description',
  genes: 'Genes', members: 'Members', member_roles: 'Roles' })[section] ?? section
const scalar = value => value == null || typeof value !== 'object'
const submittedSection = section => String(section?.label ?? '').trim().toLowerCase()
const isGenesSection = section => submittedSection(section) === 'genes'
const isMembersSection = section => submittedSection(section) === 'members'
const changed = row => ['added', 'removed', 'changed'].includes(row.operation)
const wholeMember = row => ['added', 'removed'].includes(row.operation)
const changedFields = row => (row.field_changes ?? []).filter(field => field.before !== field.after)
const retirement = row => changedFields(row).find(change => change.field === 'end_date'
  && (change.before === null) !== (change.after === null))
const rolesToDisplay = row => wholeMember(row) ? row.roles ?? [] : (row.roles ?? []).filter(changed)
const sections = computed(() => {
  const rows = props.detail.comparison?.rows ?? {}
  return [
    { key: 'genes', rows: (rows.genes ?? []).filter(changed) },
    { key: 'members', rows: (rows.members ?? []).filter(row => changed(row)
      || changedFields(row).length || (row.roles ?? []).some(changed)) },
  ].filter(section => section.rows.length)
})
const textChanges = computed(() => (props.detail.comparison?.changes ?? []).filter(change =>
  !['genes', 'members', 'member_roles'].includes(change.section)
  && scalar(change.before) && scalar(change.after) && change.before !== change.after))
</script>

<template>
  <div class="mt-3 space-y-3 rounded border p-3">
    <p v-if="detail.availability !== 'available'">Submitted application details are unavailable for this historical Review Round.</p>
    <template v-else-if="detail.mode === 'submitted_state'">
      <template v-for="section in detail.submitted_state" :key="section.label">
        <HistoricalGenes v-if="section.available && isGenesSection(section)" :genes="section.value" />
        <HistoricalMembers v-else-if="section.available && isMembersSection(section)" :members="section.value" />
        <section v-else>
          <h5 class="font-semibold">{{ section.label }}</h5>
          <HistoricalValue v-if="section.available" :value="section.value" />
          <p v-else>Historical section unavailable.</p>
        </section>
      </template>
    </template>
    <template v-else-if="detail.comparison">
      <p class="text-sm">Comparison covers captured names, descriptions, genes, members, roles and retirement. Other application fields are not compared.</p>
      <p v-for="section in detail.comparison.unavailable_sections" :key="section">
        {{ label(section) }}: Historical section unavailable.
      </p>
      <p v-if="!textChanges.length && !sections.length">No tracked changes detected for this Review Round.</p>
      <section v-for="(change, index) in textChanges" :key="index">
        <h5 class="font-semibold">{{ label(change.section) }}</h5>
        <InlineTextDiff v-if="scalar(change.before) && scalar(change.after)" :before="String(change.before ?? '')" :after="String(change.after ?? '')" />
      </section>
      <section v-for="section in sections" :key="section.key">
        <h5 class="font-semibold">{{ label(section.key) }}</h5>
        <div v-for="row in section.rows" :key="row.key" class="my-2 border-b pb-2">
          <del v-if="row.operation === 'removed'" class="text-red-700">{{ row.before?.label ?? row.before?.gene_symbol }} — Removed</del>
          <ins v-else-if="row.operation === 'added'">{{ row.after?.label ?? row.after?.gene_symbol }} — Added</ins>
          <InlineTextDiff v-else-if="section.key === 'members' && row.before?.label && row.after?.label && row.before.label !== row.after.label" :before="row.before.label" :after="row.after.label" />
          <span v-else>{{ row.after?.label ?? row.after?.gene_symbol }}</span>
          <p v-if="retirement(row)">Retirement: {{ retirement(row).before === null ? 'Active' : 'Retired' }} → {{ retirement(row).after === null ? 'Active' : 'Retired' }}</p>
          <p v-for="role in rolesToDisplay(row)" :key="role.key">
            <span v-if="wholeMember(row)">{{ (role.after ?? role.before)?.label ?? (role.after ?? role.before)?.name }}</span>
            <del v-else-if="role.operation === 'removed'" class="text-red-700">{{ role.before?.label ?? role.before?.name }} — Removed</del>
            <ins v-else-if="role.operation === 'added'">{{ role.after?.label ?? role.after?.name }} — Added</ins>
            <span v-else>{{ role.after?.label ?? role.after?.name }}</span>
          </p>
          <p v-if="row.roles_available === false">Historical roles unavailable.</p>
          <p v-for="field in row.unavailable_fields ?? []" :key="field">{{ field }}: Historical field unavailable.</p>
          <div v-for="field in changedFields(row).filter(field => field.field !== 'end_date' || !retirement(row))" :key="field.field">
            {{ field.field === 'end_date' ? 'Retirement date' : field.field }}: <del><HistoricalValue :value="field.before" /></del> → <ins><HistoricalValue :value="field.after" /></ins>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
