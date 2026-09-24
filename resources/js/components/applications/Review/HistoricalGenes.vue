<script setup>
import { computed } from 'vue'

const props = defineProps({
  genes: {
    type: Array,
    default: () => [],
  },
})

const rows = computed(() => Array.isArray(props.genes) ? props.genes : [])
const own = (object, key) => Object.prototype.hasOwnProperty.call(object ?? {}, key)
const field = (record, key) => {
  if (own(record, key)) return record[key]
  if (own(record?.attributes, key)) return record.attributes[key]
  return undefined
}
const prettyKey = key => String(key).replaceAll('_', ' ')
const compact = value => {
  if (value === null || value === undefined || value === '') return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (Array.isArray(value)) {
    const values = value.map(compact).filter(item => item !== '—')
    return values.length ? values.join(', ') : '—'
  }
  if (typeof value === 'object') {
    const preferred = value.label ?? value.display_name ?? value.name ?? value.value
    if (preferred !== undefined && preferred !== null && preferred !== '') return String(preferred)

    const values = Object.entries(value)
      .filter(([, item]) => item !== null && item !== undefined && item !== '')
      .map(([key, item]) => `${prettyKey(key)}: ${compact(item)}`)
    return values.length ? values.join('; ') : '—'
  }
  return String(value)
}
const geneSymbol = gene => compact(field(gene, 'gene_symbol') ?? field(gene, 'symbol') ?? field(gene, 'label'))
const hgncId = gene => compact(field(gene, 'hgnc_id'))
const diseaseName = gene => compact(field(gene, 'disease_name') ?? field(gene, 'disease_entity'))
const mondoId = gene => compact(field(gene, 'mondo_id'))
const moi = gene => compact(field(gene, 'moi'))
const tier = gene => compact(field(gene, 'tier'))
const plan = gene => compact(field(gene, 'plan'))
</script>

<template>
  <details class="rounded border border-gray-200 bg-gray-50 px-3 py-2">
    <summary class="cursor-pointer font-semibold">
      Genes ({{ rows.length }})
    </summary>

    <div v-if="rows.length" class="mt-3 overflow-x-auto">
      <table class="min-w-full border-collapse text-left text-sm">
        <thead>
          <tr class="border-b border-gray-300 text-gray-700">
            <th class="px-2 py-2 font-semibold">Gene</th>
            <th class="px-2 py-2 font-semibold">Disease / MONDO</th>
            <th class="px-2 py-2 font-semibold">MOI</th>
            <th class="px-2 py-2 font-semibold">Tier</th>
            <th class="px-2 py-2 font-semibold">Plan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(gene, index) in rows" :key="field(gene, 'id') ?? `${geneSymbol(gene)}-${index}`" class="border-b border-gray-200 align-top last:border-b-0">
            <td class="px-2 py-2">
              <div class="font-medium">{{ geneSymbol(gene) }}</div>
              <div v-if="hgncId(gene) !== '—'" class="text-xs text-gray-500">HGNC: {{ hgncId(gene) }}</div>
            </td>
            <td class="px-2 py-2">
              <div>{{ diseaseName(gene) }}</div>
              <div v-if="mondoId(gene) !== '—'" class="text-xs text-gray-500">{{ mondoId(gene) }}</div>
            </td>
            <td class="px-2 py-2 whitespace-nowrap">{{ moi(gene) }}</td>
            <td class="px-2 py-2 whitespace-nowrap">{{ tier(gene) }}</td>
            <td class="px-2 py-2">{{ plan(gene) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="mt-2 text-sm text-gray-500">No genes recorded.</p>
  </details>
</template>
