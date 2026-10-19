<script setup>
import HistoricalValue from './HistoricalValue.vue'
import { historicalFieldValue, isHistoricalBooleanField, isInlineHistoricalField } from './historical_fields'
import HistoricalGenes from './HistoricalGenes.vue'
import { geneFieldLabel, geneFieldValue, visibleGeneField } from './historical_gene_fields'
import HistoricalMembers from './HistoricalMembers.vue'
import InlineTextDiff from '@/components/groups/InlineTextDiff.vue'
import HistoricalSustainedCurationChanges from './HistoricalSustainedCurationChanges.vue'
import { sustainedCurationChanges } from './sustained_curation_changes'
import HistoricalSustainedCuration from './HistoricalSustainedCuration.vue'
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
const sustainedCurationSections = new Set([
  'sc-vcep standardized review process',
  'vcep standardized review process',
  'standardized review process',
  'curation and review process notes',
  'evidence summaries',
  'core approval members',
  'biocurator trainers',
  'trained biocurators',
])
const normalizeSection = value => String(value ?? '').replace(/[._-]+/g, ' ').replace(/\s+/g, ' ').replace(/:$/, '').trim().toLowerCase()
const isSustainedCurationSection = section => {
  const key = normalizeSection(section?.key)
  const sectionLabel = normalizeSection(section?.label)
  return [...sustainedCurationSections].map(normalizeSection).some(name => sectionLabel === name || key === name || key.endsWith(` ${name}`))
}

const isSustainedCurationChange = change => isSustainedCurationSection({
  key: change?.key ?? change?.section, label: change?.label,
})
const changed = row => ['added', 'removed', 'changed'].includes(row.operation)
const wholeMember = row => ['added', 'removed'].includes(row.operation)
const changedFields = row => (row.field_changes ?? []).filter(field => field.before !== field.after)
const displayedFields = (row, section) => changedFields(row).filter(field => section !== 'genes' || visibleGeneField(field.field, props.detail.group_type))
const unavailableFields = (row, section) => (row.unavailable_fields ?? []).filter(field => section !== 'genes' || visibleGeneField(field, props.detail.group_type))
const retirement = row => changedFields(row).find(change => change.field === 'end_date' && (change.before === null) !== (change.after === null))
const rolesToDisplay = row => wholeMember(row) ? row.roles ?? [] : (row.roles ?? []).filter(changed)
const sections = computed(() => {
  const rows = props.detail.comparison?.rows ?? {}
  return [
    { key: 'genes', rows: (rows.genes ?? []).filter(changed) },
    { key: 'members', rows: (rows.members ?? []).filter(row => changed(row) || changedFields(row).length || (row.roles ?? []).some(changed)) },
  ].filter(section => section.rows.length)
})
const textChanges = computed(() => (props.detail.comparison?.changes ?? []).filter(change => !['genes', 'members', 'member_roles'].includes(change.section) && change.before !== change.after
  && (!isSustainedCurationChange(change) || sustainedCurationChanges(change.before, change.after)?.length !== 0)))
</script>

<template>
  <div class="mt-3 space-y-3 rounded border p-3">
    <p v-if="detail.availability !== 'available'">Submitted application details are unavailable for this historical Review Round.</p>
    <template v-else-if="detail.mode === 'submitted_state'">
      <template v-for="section in detail.submitted_state" :key="section.label">
        <HistoricalGenes v-if="section.available && isGenesSection(section)" :genes="section.value" :group-type="detail.group_type" />
        <HistoricalMembers v-else-if="section.available && isMembersSection(section)" :members="section.value" />
        <HistoricalSustainedCuration v-else-if="isSustainedCurationSection(section)" :section="section" />
        <section v-else :class="isInlineHistoricalField(section) ? 'flex flex-wrap items-baseline gap-x-1' : ''">
          <h5 class="font-semibold">
            {{ section.label }}{{ isInlineHistoricalField(section) ? ':' : '' }}
          </h5>{{ isInlineHistoricalField(section) ? ' ' : '' }}
          <HistoricalValue v-if="section.available" :value="section.value" :boolean="isHistoricalBooleanField(section.key)" />
          <p v-else>Historical section unavailable.</p>
        </section>
      </template>
    </template>
    <template v-else-if="detail.comparison">
      <p class="text-sm">Changes between frozen Review Rounds. Unavailable historical fields are not treated as empty.</p>
      <p v-for="section in detail.comparison.unavailable_sections" :key="section">
        {{ label(section) }}: Historical section unavailable.
      </p>
      <p v-if="!textChanges.length && !sections.length">No tracked changes detected for this Review Round.</p>
      <section v-for="(change, index) in textChanges" :key="index">
        <h5 class="font-semibold">{{ change.label ?? label(change.section) }}</h5>
        <InlineTextDiff v-if="scalar(change.before) && scalar(change.after)" :before="String(historicalFieldValue(change.key ?? change.section, change.before) ?? 'Not recorded')" :after="String(historicalFieldValue(change.key ?? change.section, change.after) ?? 'Not recorded')" />
        <HistoricalSustainedCurationChanges v-else-if="isSustainedCurationChange(change)" :change="change" />
        <div v-else class="grid gap-3 md:grid-cols-2">
          <div>
            <strong>Previous</strong>
            <HistoricalValue :value="change.before" />
          </div>
          <div>
            <strong>Submitted</strong>
            <HistoricalValue :value="change.after" />
          </div>
        </div>
      </section>
      <section v-for="section in sections" :key="section.key">
        <h5 class="font-semibold">{{ label(section.key) }}</h5>
        <div v-for="row in section.rows" :key="row.key" class="my-2 border-b pb-2">
          <del v-if="row.operation === 'removed'" class="text-red-700">{{ row.before?.label ?? row.before?.gene_symbol }} — Removed</del>
          <ins v-else-if="row.operation === 'added'">{{ row.after?.label ?? row.after?.gene_symbol }} — Added</ins>
          <InlineTextDiff v-else-if="section.key === 'members' && row.before?.label && row.after?.label && row.before.label !== row.after.label" :before="row.before.label" :after="row.after.label" />
          <span v-else>{{ row.after?.label ?? row.after?.gene_symbol }}</span>
          <p v-if="section.key === 'genes' && ['added', 'removed'].includes(row.operation)">Tier: {{ geneFieldValue('tier', (row.after ?? row.before)?.tier, detail.group_type) }}</p>
          <p v-if="retirement(row)">Retirement: {{ retirement(row).before === null ? 'Active' : 'Retired' }} → {{ retirement(row).after === null ? 'Active' : 'Retired' }}</p>
          <p v-for="role in rolesToDisplay(row)" :key="role.key">
            <span v-if="wholeMember(row)">{{ (role.after ?? role.before)?.label ?? (role.after ?? role.before)?.name }}</span>
            <del v-else-if="role.operation === 'removed'" class="text-red-700">{{ role.before?.label ?? role.before?.name }} — Removed</del>
            <ins v-else-if="role.operation === 'added'">{{ role.after?.label ?? role.after?.name }} — Added</ins>
            <span v-else>{{ role.after?.label ?? role.after?.name }}</span>
          </p>
          <p v-if="row.roles_available === false">Historical roles unavailable.</p>
          <p v-for="field in unavailableFields(row, section.key)" :key="field">{{ section.key === 'genes' ? geneFieldLabel(field) : field }}: Historical field unavailable.</p>
          <div v-for="field in displayedFields(row, section.key).filter(field => field.field !== 'end_date' || !retirement(row))" :key="field.field">
            {{ section.key === 'genes' ? geneFieldLabel(field.field) : field.field === 'end_date' ? 'Retirement date' : field.field }}: <del><HistoricalValue :value="section.key === 'genes' ? geneFieldValue(field.field, field.before, detail.group_type) : field.before" /></del> → <ins><HistoricalValue :value="section.key === 'genes' ? geneFieldValue(field.field, field.after, detail.group_type) : field.after" /></ins>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
