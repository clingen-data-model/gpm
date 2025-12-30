<script setup>
import { ref, computed } from 'vue'
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
const selectedStatus = ref('')
const sortKey = ref('gene_symbol') 
const sortOrder = ref('asc')
const currentPage = ref(1)
const pageSize = ref(20)

const expanded = ref([])          
const selectedGenes = ref([])
const bulkTier = ref('')
const savingTierFor = ref(null)
const savingBulk = ref(false)

const showConfirmRemove = ref(false);
const selectedGene = ref(null);
const confirmRemove = (gene) => {
	selectedGene.value = gene;
	showConfirmRemove.value = true;
};
const cancelRemove = () => {
	selectedGene.value = null;
	showConfirmRemove.value = false;
};
const removeGene = async () => {
	if (!selectedGene.value) return;
	try {
		await api.delete(`/api/groups/${props.groupID}/expert-panel/genes/${selectedGene.value.id}`);
		showConfirmRemove.value = false;
		store.commit('pushSuccess', `Successfully removed gene ${selectedGene.value.gene_symbol}`);
		const removed = selectedGene.value;
		selectedGene.value = null;
		emit('removed', { id: removed.id, gene_symbol: removed.gene_symbol })
	} catch (error) {
		store.commit('pushError', error.response?.data);
	}
};

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
		const symbolMatch = g.gene_symbol?.toLowerCase().includes(kw)
		const epMatch = (g.expert_panels || []).some(ep => ep?.toLowerCase().includes(kw))
		const diseaseMatch = (g.details || []).some(d =>
		d?.disease_name?.toLowerCase().includes(kw) || d?.mondo_id?.toLowerCase().includes(kw)
		)
		const statusMatch = (g.statuses || []).some(s => s?.toLowerCase().includes(kw))
		const matchesSearch = !kw || symbolMatch || epMatch || diseaseMatch || statusMatch
		const matchesStatus = !selectedStatus.value || (g.statuses || []).includes(selectedStatus.value)
		return matchesSearch && matchesStatus
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


</script>

<template>
  <div class="space-y-3">
    <!-- Toolbar (search, status filter, sort, page size) -->
    <div class="mb-3 flex items-center justify-between border rounded-lg bg-white px-3 py-2">
      <div class="flex items-center gap-3">
        <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" v-if="editing" />
        <input
          v-model="search"
          type="text"
          placeholder="Search genes, statuses, diseases…"
          class="border rounded px-2 py-1 text-sm w-64"
        />
        <div class="flex items-center gap-2">
          <label class="text-xs text-gray-500">Status</label>
          <select v-model="selectedStatus" class="border rounded px-2 py-1 text-sm">
            <option value="">All</option>
            <option v-for="s in allStatuses" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
      </div>

      <div class="flex items-center gap-3">
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

    <!-- Bulk Tier Update Bar -->
    <div v-if="selectedGenes.length > 0 && editing && !readonly" class="flex items-center gap-2 bg-gray-50 p-3 border rounded">
      {{ selectedGenes.length }} gene(s) selected.
      <span class="font-semibold">Bulk Tier Update:</span>
      <select v-model="bulkTier" class="border rounded px-2 py-1 text-sm">
        <option value="">Select Tier</option>
        <option value="1">Primary</option>
        <option value="2">Secondary</option>
      </select>
      <button
        class="bg-blue-600 text-white px-3 py-1 rounded disabled:opacity-50"
        @click="applyBulkTier"
        :disabled="!bulkTier || selectedGenes.length === 0 || savingBulk"
      >
        {{ savingBulk ? 'Applying…' : 'Apply' }}
      </button>
      <button type="button" class="border rounded px-3 py-1 text-sm bg-white hover:bg-gray-50" @click="clearSelection" title="Clear selection">
        Clear
      </button>
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
            <div class="flex items-start gap-3">
              <div v-if="editing && !readonly" class="mt-1">
                <input
                  type="checkbox"
                  :checked="selectedGenes.includes(gene.id)"
                  @change="toggleSelect(gene.id)"
                  :aria-label="`Select ${gene.gene_symbol}`"
                />
              </div>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-base font-semibold text-gray-900">{{ gene.gene_symbol }}</span>
                  <span v-if="(gene.statuses || []).length" class="text-xs rounded-full px-2 py-0.5 text-gray-700" :class="(gene.details || []).length ? 'border border-amber-400 bg-amber-50' : 'border border-gray-400 bg-gray-50'">
                    {{ (gene.statuses || []).join(', ') }}
                  </span>
                </div>
                <div class="mt-0.5 text-sm text-gray-700 truncate">
                  <template v-if="(gene.expert_panels || []).length">
                    {{ gene.expert_panels.join(', ') }}
                  </template>
                  <template v-else>—</template>
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
				<button class="rounded border px-2 py-1 text-xs bg-white hover:bg-red-50"
					:disabled="removingId === gene.id"
					@click="confirmRemove(gene)"
					title="Remove gene"
					>
					{{ removingId === gene.id ? 'Removing…' : ' X ' }}
				</button>
              </template>
              <span v-else class="text-xs text-gray-700">Tier: {{ gene.tier || '—' }}</span>

              
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
                    <div><span class="text-[11px] uppercase text-gray-500">Classification</span><div class="text-gray-900">{{ entry.classification || '—' }}</div></div>
                    <div><span class="text-[11px] uppercase text-gray-500">MOI</span><div class="text-gray-900">{{ entry.moi_name || '—' }}</div></div>
					          <div><span class="text-[11px] uppercase text-gray-500">Type</span><div class="text-gray-900">{{ entry.curation_type || '—' }}</div></div>
                    <div><span class="text-[11px] uppercase text-gray-500">Rationales</span><div class="text-gray-900">{{ entry.rationales || '—' }}</div></div>
                    <div class="col-span-2"><span class="text-[11px] uppercase text-gray-500">Phenotype</span>
                      <div class="text-gray-900">
                        <span>{{ entry.phenotypes || '—' }}</span>
                        <span class="mx-2 font-bold">&middot;</span>
                        <span class="font-bold">Excluded:</span> {{ entry.excluded_phenotypes }}
                      </div></div>
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
            <p class="mb-6">Are you sure you want to remove <strong>{{ selectedGene?.gene_symbol }}</strong>?</p>
            <div class="flex justify-end gap-2">
                <button class="btn btn-gray" @click="cancelRemove">Cancel</button>
                <button class="btn btn-red" @click="removeGene">Remove</button>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped> 
.fade-enter-active, .fade-leave-active { transition: all 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
