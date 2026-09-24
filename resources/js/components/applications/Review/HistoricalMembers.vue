<script setup>
import { computed } from 'vue'

const props = defineProps({
  members: {
    type: Array,
    default: () => [],
  },
})

const rows = computed(() => Array.isArray(props.members) ? props.members : [])
const own = (object, key) => Object.prototype.hasOwnProperty.call(object ?? {}, key)
const field = (record, key) => {
  if (own(record, key)) return record[key]
  if (own(record?.attributes, key)) return record.attributes[key]
  return undefined
}
const person = member => field(member, 'person') ?? member?.relations?.person ?? null
const personField = (member, key) => field(member, key) ?? field(person(member), key)
const compact = value => {
  if (value === null || value === undefined || value === '') return '—'
  if (typeof value === 'object') return value.label ?? value.display_name ?? value.name ?? '—'
  return String(value)
}
const memberName = member => {
  const first = compact(personField(member, 'first_name'))
  const last = compact(personField(member, 'last_name'))
  const parts = [first, last].filter(value => value !== '—')
  return parts.length ? parts.join(' ') : compact(personField(member, 'label') ?? personField(member, 'name'))
}
const email = member => compact(personField(member, 'email'))
const roleLabel = role => {
  if (role === null || role === undefined || role === '') return '—'

  // Historical submitted-state snapshots may store roles as plain labels,
  // e.g. ['Coordinator', 'Chair'], rather than full role objects.
  if (typeof role !== 'object') return compact(role)

  return compact(
    field(role, 'display_name')
      ?? field(role, 'label')
      ?? field(role, 'name')
  )
}
const roles = member => {
  const value = field(member, 'roles') ?? member?.relations?.roles ?? []
  if (!Array.isArray(value) || !value.length) return '—'

  const labels = value
    .map(roleLabel)
    .filter(label => label !== '—')

  return labels.length ? labels.join(', ') : '—'
}
const retirement = member => {
  if (own(member, 'retirement') || own(member?.attributes, 'retirement')) return compact(field(member, 'retirement'))
  if (own(member, 'end_date') || own(member?.attributes, 'end_date')) return field(member, 'end_date') === null ? 'Active' : 'Retired'
  return '—'
}
</script>

<template>
  <details class="rounded border border-gray-200 bg-gray-50 px-3 py-2">
    <summary class="cursor-pointer font-semibold">
      Members ({{ rows.length }})
    </summary>

    <div v-if="rows.length" class="mt-3 overflow-x-auto">
      <table class="min-w-full border-collapse text-left text-sm">
        <thead>
          <tr class="border-b border-gray-300 text-gray-700">
            <th class="px-2 py-2 font-semibold">Member</th>
            <th class="px-2 py-2 font-semibold">Email</th>
            <th class="px-2 py-2 font-semibold">Roles</th>
            <th class="px-2 py-2 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(member, index) in rows" :key="field(member, 'id') ?? personField(member, 'id') ?? `${memberName(member)}-${index}`" class="border-b border-gray-200 align-top last:border-b-0">
            <td class="px-2 py-2 font-medium">{{ memberName(member) }}</td>
            <td class="px-2 py-2">{{ email(member) }}</td>
            <td class="px-2 py-2">{{ roles(member) }}</td>
            <td class="px-2 py-2 whitespace-nowrap">{{ retirement(member) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="mt-2 text-sm text-gray-500">No members recorded.</p>
  </details>
</template>