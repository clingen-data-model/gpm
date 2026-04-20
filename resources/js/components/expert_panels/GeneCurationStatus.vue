<script setup>
import { ref, computed, watch } from 'vue'
import { useStore } from 'vuex'
import api from '@/http/api'

const props = defineProps({
	genes: { type: Array, required: true, default: () => [] },
	groupID: { type: String, required: true },
	editing: { type: Boolean, default: true },
	readonly: { type: Boolean, default: false },
})

const emit = defineEmits(['removed'])

const store = useStore()

const search = ref('')
const selectedStatuses = ref([])
const statusMenuOpen = ref(false)
const sortKey = ref('gene_symbol') 
const sortOrder = ref('asc')
const currentPage = ref(1)
const pageSize = ref(20)
const showFilters = ref(false)

const filterTier = ref('')
const filterMoi = ref('')
const filterClassification = ref('')
const filterCurationType = ref('')

const detailEntries = (gene) => Array.isArray(gene.details) ? gene.details : []

const normalize = (value) => (value ?? '').toString().trim()

const matchesDetailField = (gene, field, selectedValue) => {
	if (!selectedValue) return true
	return detailEntries(gene).some(entry => normalize(entry?.[field]) === selectedValue)
}

const expanded = ref([])          
const selectedGenes = ref([])
const bulkTier = ref('')
const savingTierFor = ref(null)
const savingBulk = ref(false)

// Updated to support bulk removal
const showConfirmRemove = ref(false)
const genesPendingRemoval = ref([])
const removingIds = ref([])
const removingBulk = ref(false)

const selectedGeneRows = computed(() => {
  return props.genes.filter(g => selectedGenes.value.includes(g.id))
})

const removalCount = computed(() => genesPendingRemoval.value.length)

const removalLabel = computed(() => {
  if (removalCount.value === 0) return ''
  if (removalCount.value === 1) return genesPendingRemoval.value[0]?.gene_symbol || ''
  return `${removalCount.value} selected genes`
})

const confirmRemove = (geneOrGenes) => {
  const list = Array.isArray(geneOrGenes) ? geneOrGenes : [geneOrGenes]
  genesPendingRemoval.value = list.filter(Boolean)
  if (!genesPendingRemoval.value.length) return
  showConfirmRemove.value = true
}

const cancelRemove = () => {
  genesPendingRemoval.value = []
  showConfirmRemove.value = false
}
const removeGenes = async () => {
  if (!genesPendingRemoval.value.length) return
  const ids = genesPendingRemoval.value.map(g => g.id)
  const names = genesPendingRemoval.value.map(g => g.gene_symbol).filter(Boolean)
  removingIds.value = ids
  removingBulk.value = ids.length > 1
  try {
    await api.delete(`/api/groups/${props.groupID}/expert-panel/genes`, { data: { ids } })
    showConfirmRemove.value = false
    store.commit('pushSuccess', ids.length === 1 ? `Successfully removed gene ${names[0]}` : `Successfully removed ${ids.length} genes`)
    selectedGenes.value = selectedGenes.value.filter(id => !ids.includes(id))
    genesPendingRemoval.value = []
    emit('removed', { ids, gene_symbols: names })
  } catch (error) {
    store.commit('pushError', error.response?.data || 'Failed to remove gene(s)')
  } finally {
    removingIds.value = []
    removingBulk.value = false
  }
}
// End of updated removal logic

const toggleStatus = (s) => {
  if (selectedStatuses.value.includes(s)) {
    selectedStatuses.value = selectedStatuses.value.filter(x => x !== s)
  } else {
    selectedStatuses.value = [...selectedStatuses.value, s]
  }
}
const clearStatuses = () => { selectedStatuses.value = [] }
const statusLabel = computed(() => {
  if (selectedStatuses.value.length === 0) return 'All'
  if (selectedStatuses.value.length === 1) return selectedStatuses.value[0]
  return `${selectedStatuses.value.length} selected`
})

const statusPriority = {
	'Not Curated': 0,
	'Uploaded': 1,
	'Precuration': 2,
	'Disease entity assigned': 3,
	'Precuration Complete': 4,
	'Curation Provisional': 5,
	'Curation Approved': 6,
	'Recuration assigned': 7,
	'Retired Assignment': 8,
	'Published': 9,
}

const allStatuses = computed(() => {
	const set = new Set()
	props.genes.forEach(g => g.statuses.forEach(s => set.add(s)))
	return Array.from(set).sort((a, b) => (statusPriority[a] ?? 0) - (statusPriority[b] ?? 0))
})

const filteredGenes = computed(() => {
	const kw = search.value.trim().toLowerCase()

	return props.genes.filter(g => {
		const details = detailEntries(g)

		const symbolMatch = g.gene_symbol?.toLowerCase().includes(kw)
		const epMatch = (g.expert_panels || []).some(ep => ep?.toLowerCase().includes(kw))
		const diseaseMatch = details.some(d => d?.disease_name?.toLowerCase().includes(kw) || d?.mondo_id?.toLowerCase().includes(kw))
		const statusSearchMatch = (g.statuses || []).some(s => s?.toLowerCase().includes(kw))
		const detailSearchMatch = details.some(d => d?.classification?.toLowerCase().includes(kw) || d?.moi_name?.toLowerCase().includes(kw) || d?.curation_type?.toLowerCase().includes(kw) || d?.curation_status?.toLowerCase().includes(kw))
		const matchesSearch = !kw || symbolMatch || epMatch || diseaseMatch || statusSearchMatch || detailSearchMatch
		const matchesStatus = selectedStatuses.value.length === 0 || (g.statuses || []).some(s => selectedStatuses.value.includes(s)) || details.some(d => selectedStatuses.value.includes(d?.curation_status))
		// const matchesTier = !filterTier.value || String(g.tier ?? '') === filterTier.value
    const matchesTier = !filterTier.value ? true : filterTier.value === 'none' ? (g.tier === null || g.tier === undefined || g.tier === '') : String(g.tier) === filterTier.value
		const matchesMoi = matchesDetailField(g, 'moi_name', filterMoi.value)
		const matchesClassification = matchesDetailField(g, 'classification', filterClassification.value)
		const matchesCurationType = matchesDetailField(g, 'curation_type', filterCurationType.value)

		return ( matchesSearch && matchesStatus && matchesTier && matchesMoi && matchesClassification && matchesCurationType
		)
	})
})

const sortedGenes = computed(() => {
  return [...filteredGenes.value].sort((a, b) => {
    if (sortKey.value === 'statuses') {
      const pri = (g) => {
        const arr = Array.isArray(g.statuses) ? g.statuses : []
        const vals = arr.map(s => statusPriority[s] ?? 0)
        return vals.length ? Math.min(...vals) : 0
      }
      const aP = pri(a), bP = pri(b)
      return sortOrder.value === 'asc' ? bP - aP : aP - bP
    }

    if (sortKey.value === 'tier') {
      const aN = Number(a.tier || 0)
      const bN = Number(b.tier || 0)
      return sortOrder.value === 'asc' ? aN - bN : bN - aN
    }

    let aVal, bVal
    if (sortKey.value === 'expert_panel') {
      aVal = ((a.expert_panels || []).join(', ')).toLowerCase()
      bVal = ((b.expert_panels || []).join(', ')).toLowerCase()
    } else { 
      aVal = (a.gene_symbol ?? '').toString().toLowerCase()
      bVal = (b.gene_symbol ?? '').toString().toLowerCase()
    }

    return sortOrder.value === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
  })
})

const totalPages = computed(() => Math.ceil(sortedGenes.value.length / pageSize.value))
const paginatedGenes = computed(() => {
	const start = (currentPage.value - 1) * pageSize.value
	return sortedGenes.value.slice(start, start + pageSize.value)
})

const toggleExpanded = (index) => {
	if (expanded.value.includes(index)) {
		expanded.value = expanded.value.filter(i => i !== index)
	} else {
		expanded.value.push(index)
	}
}
const toggleSelect = (id) => {
	if (selectedGenes.value.includes(id)) {
		selectedGenes.value = selectedGenes.value.filter(x => x !== id)
	} else {
		selectedGenes.value.push(id)
	}
}
const toggleSelectAll = () => {
	const idsOnPage = paginatedGenes.value.map(g => g.id)
	const allSelected = idsOnPage.every(id => selectedGenes.value.includes(id))
	if (allSelected) {
		selectedGenes.value = selectedGenes.value.filter(id => !idsOnPage.includes(id))
	} else {
		selectedGenes.value = [...new Set([...selectedGenes.value, ...idsOnPage])]
	}
}
const isAllSelected = computed(() => paginatedGenes.value.every(g => selectedGenes.value.includes(g.id)))

const updateTier = async (gene) => {
	if (props.readonly || !props.editing) return
	savingTierFor.value = gene.id
	const oldTier = gene.tier
	try {
		await api.put(`/api/groups/${props.groupID}/expert-panel/genes/update-tier`, {
		ids: [gene.id],
		tier: gene.tier || null,
		})
		store.commit('pushSuccess', `Tier updated for ${gene.gene_symbol}`)
	} catch (err) {
		gene.tier = oldTier
		store.commit('pushError', `Failed to update tier for ${gene.gene_symbol}`)
		console.error(err?.response?.data || err)
	} finally {
		savingTierFor.value = null
	}
}

const applyBulkTier = async () => {
	if (!bulkTier.value || selectedGenes.value.length === 0) return
	savingBulk.value = true
	try {
		await api.put(`/api/groups/${props.groupID}/expert-panel/genes/update-tier`, {
			ids: selectedGenes.value,
			tier: bulkTier.value,
		})
		store.commit('pushSuccess', `Tier updated for ${selectedGenes.value.length} genes`)
		// reflect locally
		props.genes.forEach(g => { if (selectedGenes.value.includes(g.id)) g.tier = bulkTier.value })
		selectedGenes.value = []
		bulkTier.value = ''
	} catch (err) {
		store.commit('pushError', 'Failed to update tiers in bulk')
	} finally {
		savingBulk.value = false
	}
}
const clearSelection = () => {
  if (selectedGenes.value.length === 0) return
  selectedGenes.value = [] 
}

const moiOptions = computed(() => {
	const set = new Set()
	props.genes.forEach(g => {
		detailEntries(g).forEach(entry => {
			if (entry?.moi_name) set.add(entry.moi_name)
		})
	})
	return Array.from(set).sort((a, b) => a.localeCompare(b))
})

const classificationOptions = computed(() => {
	const set = new Set()
	props.genes.forEach(g => {
		detailEntries(g).forEach(entry => {
			if (entry?.classification) set.add(entry.classification)
		})
	})
	return Array.from(set).sort((a, b) => a.localeCompare(b))
})

const curationTypeOptions = computed(() => {
	const set = new Set()
	props.genes.forEach(g => {
		detailEntries(g).forEach(entry => {
			if (entry?.curation_type) set.add(entry.curation_type)
		})
	})
	return Array.from(set).sort((a, b) => a.localeCompare(b))
})
const activeFilterCount = computed(() => {
	let count = 0
	if (selectedStatuses.value.length) count += 1
	if (filterTier.value) count += 1
	if (filterMoi.value) count += 1
	if (filterClassification.value) count += 1
	if (filterCurationType.value) count += 1
	return count
})

const clearFilters = () => {
	selectedStatuses.value = []
	filterTier.value = ''
	filterMoi.value = ''
	filterClassification.value = ''
	filterCurationType.value = ''
	currentPage.value = 1
}
watch(
	[search, selectedStatuses, filterTier, filterMoi, filterClassification, filterCurationType, pageSize],
	() => {
		currentPage.value = 1
	}
)

</script>

<template>
  <div class="space-y-3">
    <!-- Toolbar (search, status filter, sort, page size) -->
    <div class="mb-3 border rounded-lg bg-white px-3 py-2">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <!-- Left -->
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
          <input v-if="editing" type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" />
          <input v-model="search" type="text" placeholder="Search genes, statuses, diseases, MOI..." class="border rounded px-2 py-1 text-sm w-full sm:w-72" />
          <button type="button" class="border rounded px-3 py-1 text-sm bg-white hover:bg-gray-50" @click="showFilters = !showFilters">
            Filters <span v-if="activeFilterCount"> ({{ activeFilterCount }})</span>
          </button>
          <button v-if="activeFilterCount" type="button" class="text-sm text-blue-600 hover:underline" @click="clearFilters">Clear filters</button>
        </div>

        <!-- Right -->
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto lg:justify-end">
          <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500">Sort by</label>
            <select v-model="sortKey" class="border rounded px-2 py-1 text-sm">
              <option value="gene_symbol">HGNC Symbol</option>
              <option value="expert_panel">Expert Panel</option>
              <option value="statuses">Status Priority</option>
              <option value="tier">Tier</option>
            </select>
            <button
              class="border rounded px-2 py-1 text-xs"
              @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
              :aria-label="`Toggle sort order (currently ${sortOrder})`"
              title="Toggle sort order"
            >
              {{ sortOrder === 'asc' ? 'ASC ▲' : 'DESC ▼' }}
            </button>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500">Page size</label>
            <select v-model="pageSize" class="border rounded px-2 py-1 text-sm">
              <option v-for="size in [20, 50, 100]" :key="size" :value="size">{{ size }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Filters panel -->
      <transition name="fade">
        <div v-if="showFilters" class="mt-3 border rounded-lg bg-gray-50 p-3">
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <!-- Status -->
            <div class="relative">
              <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
              <button
                type="button"
                class="border rounded px-2 py-2 text-sm bg-white w-full flex items-center justify-between"
                @click="statusMenuOpen = !statusMenuOpen"
              >
                <span class="truncate">{{ statusLabel }}</span>
                <span class="ml-2 text-gray-400">▾</span>
              </button>

              <div
                v-if="statusMenuOpen"
                class="absolute top-full left-0 mt-1 w-full min-w-[18rem] border rounded bg-white shadow-lg z-50 p-2"
              >
                <div class="flex items-center justify-between pb-2 border-b">
                  <button type="button" class="text-xs text-blue-600" @click="clearStatuses">Clear</button>
                  <button type="button" class="text-xs text-gray-600" @click="statusMenuOpen = false">Done</button>
                </div>

                <div class="max-h-64 overflow-auto pt-2 space-y-1">
                  <label v-for="s in allStatuses" :key="s" class="flex items-center gap-2 text-sm">
                    <input type="checkbox" :checked="selectedStatuses.includes(s)" @change="toggleStatus(s)" />
                    <span>{{ s }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Tier -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Tier</label>
              <select v-model="filterTier" class="w-full border rounded px-2 py-2 text-sm bg-white">
                <option value="">All Tiers</option>
                <option value="1">Primary</option>
                <option value="2">Secondary</option>
                <option value="none">No Tier Set</option>
              </select>
            </div>

            <!-- MOI -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">MOI</label>
              <select v-model="filterMoi" class="w-full border rounded px-2 py-2 text-sm bg-white">
                <option value="">All MOIs</option>
                <option v-for="moi in moiOptions" :key="moi" :value="moi">{{ moi }}</option>
              </select>
            </div>

            <!-- Classification -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Classification</label>
              <select v-model="filterClassification" class="w-full border rounded px-2 py-2 text-sm bg-white">
                <option value="">All Classifications</option>
                <option v-for="c in classificationOptions" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <!-- Curation Type -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Curation Type</label>
              <select v-model="filterCurationType" class="w-full border rounded px-2 py-2 text-sm bg-white">
                <option value="">All Curation Types</option>
                <option v-for="type in curationTypeOptions" :key="type" :value="type">{{ type }}</option>
              </select>
            </div>
          </div>

          <div class="mt-3 flex justify-end">
            <button
              type="button"
              class="border rounded px-3 py-1 text-sm bg-white hover:bg-gray-50"
              @click="clearFilters"
            >
              Clear filters
            </button>
          </div>
        </div>
      </transition>
    </div>

    <!-- Bulk Tier Update Bar -->
    <div v-if="selectedGenes.length > 0 && editing && !readonly" class="flex flex-col gap-3 rounded border bg-gray-50 p-3 lg:flex-row lg:items-center lg:justify-between">
      <!-- Left: summary -->
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-white border px-3 py-1 text-sm font-medium text-gray-700">
          {{ selectedGenes.length }} gene(s) selected
        </span>
        <button type="button" class="border rounded px-3 py-1 text-sm bg-white hover:bg-gray-50" @click="clearSelection" title="Clear selection">Clear</button>
      <!-- 
      </div>

        Middle: bulk tier action
      <div class="flex flex-wrap items-center gap-2">  -->
        <span class="mr-2 font-semibold text-sm text-gray-700">Bulk Tier Update:</span>
        <select v-model="bulkTier" class="border rounded px-2 py-1 text-sm bg-white">
          <option value="">Select Tier</option>
          <option value="1">Primary</option>
          <option value="2">Secondary</option>
        </select>
        <button class="bg-blue-600 text-white px-3 py-1 rounded disabled:opacity-50" @click="applyBulkTier" :disabled="!bulkTier || selectedGenes.length === 0 || savingBulk">
          {{ savingBulk ? 'Applying…' : 'Apply' }}
        </button>
      </div>

      <!-- Right: selection actions -->
      <div class="flex flex-wrap items-center gap-2 lg:justify-end">
        <button type="button" class="rounded bg-red-600 text-white px-3 py-1 text-sm hover:bg-red-700 disabled:opacity-50" @click="confirmRemove(selectedGeneRows)" :disabled="selectedGenes.length === 0 || removingBulk" title="Remove selected genes">
          {{ removingBulk ? 'Removing…' : 'Remove Selected' }}
        </button>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="paginatedGenes.length === 0" class="p-6 text-center text-sm text-gray-500 border rounded-lg bg-white">
      No genes found.
    </div>

    <!-- Card list -->
    <ul v-else class="space-y-2">
      <li v-for="(gene, index) in paginatedGenes" :key="gene.id">
        <div class="rounded-2xl overflow-hidden border border-gray-200 bg-white shadow-sm hover:shadow transition-shadow">
          <!-- Top row -->
          <div class="flex items-start justify-between gap-3 p-3">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <div v-if="editing && !readonly" class="mt-1">
                <input
                  type="checkbox"
                  :checked="selectedGenes.includes(gene.id)"
                  @change="toggleSelect(gene.id)"
                  :aria-label="`Select ${gene.gene_symbol}`"
                />
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-base font-semibold text-gray-900">{{ gene.gene_symbol }}</span>
                  <span v-if="(gene.statuses || []).length" class="text-xs rounded-full px-2 py-0.5 text-gray-700" :class="(gene.details || []).length ? 'border border-amber-400 bg-amber-50' : 'border border-gray-400 bg-gray-50'">
                    {{ (gene.statuses || []).join(', ') }}
                  </span>
                </div>
                <div v-if="(gene.expert_panels || []).length" class="mt-0.5 text-sm text-gray-700 truncate max-w-full">
                    {{ gene.expert_panels.join(', ') }}
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                v-if="(gene.details || []).length"
                @click="toggleExpanded(index)"
                class="rounded border px-2 py-1 text-xs bg-white"
                :aria-expanded="expanded.includes(index)"
                :aria-controls="`gene-details-${gene.id}`"
                title="Toggle details"
              >
                {{ expanded.includes(index) ? 'Hide Details' : 'Show Details' }}
              </button>

              <template v-if="editing && !readonly">
                <select
                  v-model="gene.tier"
                  class="border rounded px-2 py-1 text-xs"
                  @change="updateTier(gene)"
                  :disabled="savingTierFor === gene.id || readonly"
                  title="Tier"
                >
                  <option value="">—</option>
                  <option value="1">Primary</option>
                  <option value="2">Secondary</option>
                </select>
                <span v-if="savingTierFor === gene.id" class="text-xs text-gray-500">Saving…</span>
                <button class="rounded px-2 py-1 text-xs border"
                  :disabled="removingIds.includes(gene.id)"
                  :class="(gene.details || []).length ? 'border-amber-400 bg-amber-50 font-semibold' : 'border-gray-400 bg-gray-50'"
                  @click="confirmRemove(gene)"
                  title="Remove gene"
                >
                  {{ removingIds.includes(gene.id) ? 'Removing…' : 'Remove?' }}
                </button>
              </template>
              <span v-else class="text-xs text-gray-700">Tier: {{ ! gene.tier ? '—' : gene.tier == 1 ? 'Primary' : 'Secondary' }}</span>
            </div>
          </div>

          <!-- Expanded details -->
          <transition name="fade">
            <div
              v-if="expanded.includes(index)"
              :id="`gene-details-${gene.id}`"
              class="px-3 pb-3 pt-2 text-sm"
            >
              <div class="space-y-2">
                <div
                  v-for="entry in gene.details"
                  :key="entry.disease_name + entry.curation_status + (entry.mondo_id || '')"
                  class="rounded-lg border border-gray-200 bg-gray-50 p-2"
                >
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    <div><span class="text-[11px] uppercase text-gray-500">Expert Panel</span><div class="text-gray-900">{{ entry.expert_panel || '—' }}</div></div>
                    <div><span class="text-[11px] uppercase text-gray-500">Status</span><div class="text-gray-900">
                      {{ entry.curation_status || '—' }}. Date:
                      {{ entry?.date_approved ? new Date(entry.date_approved).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : '—' }}
                    </div></div>                    
                    <div><span class="text-[11px] uppercase text-gray-500">Disease</span><div class="text-gray-900">{{ entry.mondo_id ? `${entry.mondo_id} ${entry.disease_name || ''}` : (entry.disease_name || '—') }}</div></div>
                    <template v-if="entry.classification || entry.moi_name || entry.curation_type">
                      <div><span class="text-[11px] uppercase text-gray-500">Classification</span><div class="text-gray-900">{{ entry.classification || '—' }}</div></div>
                      <div><span class="text-[11px] uppercase text-gray-500">MOI</span><div class="text-gray-900">{{ entry.moi_name || '—' }}</div></div>                    
					            <div><span class="text-[11px] uppercase text-gray-500">Curation Type</span><div class="text-gray-900">{{ entry.curation_type || '—' }}</div></div>
                    </template>
                    <template v-if="entry.rationales || entry.phenotypes">
                    <div><span class="text-[11px] uppercase text-gray-500">Rationales</span><div class="text-gray-900">{{ entry.rationales || '—' }}</div></div>
                    <div v-if="entry.phenotypes" class="col-span-2"><span class="text-[11px] uppercase text-gray-500">Phenotype</span>
                      <div class="text-gray-900">
                        <span>{{ entry.phenotypes || '—' }}</span>
                        <template v-if="entry.excluded_phenotypes !=''">
                          <span class="mx-2 font-bold">&middot; Excluded: </span> 
                          {{ entry.excluded_phenotypes }}
                        </template>
                      </div></div>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>
      </li>
    </ul>

    <!-- Pagination -->
    <div class="mt-4 flex justify-between items-center">
      <button class="border rounded px-3 py-1 text-sm" :disabled="currentPage === 1" @click="currentPage--">Prev</button>
      <span class="text-sm">Page {{ currentPage }} of {{ totalPages }}</span>
      <button class="border rounded px-3 py-1 text-sm" :disabled="currentPage === totalPages" @click="currentPage++">Next</button>
    </div>
	  <div v-if="showConfirmRemove" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h3 class="text-lg font-semibold mb-4">Confirm Removal</h3>
        <p v-if="removalCount === 1" class="mb-6">Are you sure you want to remove <strong>{{ removalLabel }}</strong>?</p>
        <p v-else class="mb-6">Are you sure you want to remove <strong>{{ removalCount }}</strong> selected genes?</p>
        <div class="flex justify-end gap-2">
          <button class="btn btn-gray" @click="cancelRemove">Cancel</button>
          <button class="btn btn-red" @click="removeGenes">{{ removingBulk || removingIds.length ? 'Removing…' : 'Remove' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped> 
.fade-enter-active, .fade-leave-active { transition: all 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
