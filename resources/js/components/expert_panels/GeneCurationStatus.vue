<script setup>
import { ref, computed } from 'vue'
import { useStore } from 'vuex'
import api from '@/http/api'

const props = defineProps({
    genes: {
        type: Array,
        required: true,
        default: () => [],
    },
	groupID: {
		type: String,
		required: true
	},
	editing: {
		type: Boolean,
		default: true
	},
	readonly: {
		type: Boolean,
		default: false
	}

})

const store = useStore()

// State
const search = ref('')
const currentPage = ref(1)
const pageSize = ref(20)
const savingTierFor = ref(null)
const expanded = ref([])
const selectedGenes = ref([]) // For bulk selection
const bulkTier = ref('')
const savingBulk = ref(false)

// Sorting
const sortKey = ref('gene_symbol')
const sortOrder = ref('asc')

// Filter Status
const selectedStatus = ref('')

// Status priority mapping
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
    'Published': 9
}

const allStatuses = computed(() => {
    const set = new Set();
    props.genes.forEach(g => g.statuses.forEach(s => set.add(s)));
    return Array.from(set).sort((a, b) => statusPriority[a] - statusPriority[b]);
});

// Filter genes
const filteredGenes = computed(() => {
    const keyword = search.value.toLowerCase()
    return props.genes.filter(gene => {        
        const symbolMatch = gene.gene_symbol.toLowerCase().includes(keyword)
        const statusMatch = gene.statuses.some(status =>
            status.toLowerCase().includes(keyword)
        )

        // Apply search
        const matchesSearch = !search.value || symbolMatch || statusMatch

        // Apply status filter
        const matchesStatus = !selectedStatus.value || gene.statuses.includes(selectedStatus.value)

        return matchesSearch && matchesStatus
    })
})

// Sort genes
const sortedGenes = computed(() => {
    return [...filteredGenes.value].sort((a, b) => {
        let aVal, bVal
        if (sortKey.value === 'statuses') {
            const maxA = Math.max(...a.statuses.map(s => statusPriority[s] || 0))
            const maxB = Math.max(...b.statuses.map(s => statusPriority[s] || 0))
            aVal = maxA
            bVal = maxB
        } else if (sortKey.value === 'tier') {
            aVal = a.tier || 0
            bVal = b.tier || 0
        } else {
            aVal = a.gene_symbol
            bVal = b.gene_symbol
        }
        return sortOrder.value === 'asc' ? (aVal > bVal ? 1 : -1) : (aVal < bVal ? 1 : -1)
    })
})

// Pagination
const totalPages = computed(() => Math.ceil(sortedGenes.value.length / pageSize.value))
const paginatedGenes = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value
    return sortedGenes.value.slice(start, start + pageSize.value)
})

// Expand row
const toggleExpanded = index => {
    if (expanded.value.includes(index)) {
        expanded.value = expanded.value.filter(i => i !== index)
    } else {
        expanded.value.push(index)
    }
}

// Selection
const toggleSelect = geneId => {
    if (selectedGenes.value.includes(geneId)) {
        selectedGenes.value = selectedGenes.value.filter(id => id !== geneId)
    } else {
        selectedGenes.value.push(geneId)
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

const isAllSelected = computed(() =>
    paginatedGenes.value.every(g => selectedGenes.value.includes(g.id))
)

// Update single tier
const updateTier = async gene => {
	console.log(props.groupID)
	if (props.readonly || !props.editing) return
    savingTierFor.value = gene.id
	const oldTier = gene.tier
    try {
        await api.put(`/api/groups/${props.groupID}/expert-panel/genes/update-tier`, {
			ids: [gene.id],
			tier: gene.tier || null
		})
		store.commit('pushSuccess', `Tier updated for ${gene.gene_symbol}`)
    } catch (error) {
		gene.tier = oldTier
        store.commit('pushError', `Failed to update tier for ${gene.gene_symbol}`)
		console.error(error.response?.data || error.message)
    } finally {
        savingTierFor.value = null
    }
}

// Bulk update tier
const applyBulkTier = async () => {	
    if (!bulkTier.value || selectedGenes.value.length === 0) return
    savingBulk.value = true
    try {
        await api.put(`/api/groups/${props.groupID}/expert-panel/genes/update-tier`, {
			ids: selectedGenes.value,
			tier: bulkTier.value
		})
		store.commit('pushSuccess', `Tier updated for ${selectedGenes.value.length} genes`)
		// Update local state so dropdowns reflect the new tier
		props.genes.forEach(g => {
			if (selectedGenes.value.includes(g.id)) {
				g.tier = bulkTier.value
			}
		})
		selectedGenes.value = []
		bulkTier.value = ''
    } catch (error) {
        store.commit('pushError', 'Failed to update tiers in bulk')
    } finally {
        savingBulk.value = false
    }
}

// Change sorting
const changeSort = key => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}
</script>

<template>
<div class="space-y-4">
    <!-- Search and Page Size -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
		<!-- Search -->
		<input
			v-model="search"
			type="text"
			placeholder="Search gene or status..."
			class="border px-3 py-1.5 rounded text-sm w-full sm:w-64"
		/>

		<!-- Status Filter Dropdown -->
		<div class="flex items-center gap-2">
			<label class="text-sm">Filter by Status:</label>
			<select v-model="selectedStatus" class="border rounded px-2 py-1 text-sm">
				<option value="">All</option>
				<option v-for="status in allStatuses" :key="status" :value="status">{{ status }}</option>
			</select>
		</div>

		<!-- Page Size -->
		<div class="flex items-center gap-2">
			<label class="text-sm">Page size:</label>
			<select v-model="pageSize" class="border px-2 py-1 rounded text-sm">
				<option v-for="size in [20, 50, 100]" :key="size" :value="size">{{ size }}</option>
			</select>
		</div>
	</div>

    <!-- Bulk Action Bar -->
    <div v-if="selectedGenes.length > 0 && editing && !readonly" class="flex items-center gap-2 bg-gray-50 p-3 border rounded mb-3">
		<span class="font-semibold">Bulk Tier Update:</span>
		<select v-model="bulkTier" class="border rounded px-2 py-1 text-sm">
			<option value="">Select Tier</option>
			<option value="1">Tier 1</option>
			<option value="2">Tier 2</option>
			<option value="3">Tier 3</option>
			<option value="4">Tier 4</option>
		</select>
		<button 
			class="bg-blue-600 text-white px-3 py-1 rounded disabled:opacity-50" 
			@click="applyBulkTier" 
			:disabled="!bulkTier || selectedGenes.length === 0"
		>
			Apply
		</button>
	</div>

    <!-- Table -->
    <div class="border">
        <table class="table-auto w-full border min-w-full">
            <thead class="bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th v-if="editing && !readonly" class="px-2"><input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" :disabled="readonly || !editing" /></th>
                    <th class="text-left px-4 py-2 cursor-pointer" @click="changeSort('gene_symbol')">
                        Gene <span v-if="sortKey==='gene_symbol'">{{ sortOrder==='asc'?'▲':'▼' }}</span>
                    </th>
                    <th class="text-left px-4 py-2 cursor-pointer" @click="changeSort('statuses')">
                        Statuses <span v-if="sortKey==='statuses'">{{ sortOrder==='asc'?'▲':'▼' }}</span>
                    </th>
                    <th class="text-left px-4 py-2 cursor-pointer" @click="changeSort('tier')">
                        Tier <span v-if="sortKey==='tier'">{{ sortOrder==='asc'?'▲':'▼' }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(gene, index) in paginatedGenes" :key="gene.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-2" v-if="editing && !readonly">
                            <input type="checkbox" :checked="selectedGenes.includes(gene.id)" @change="toggleSelect(gene.id)" :disabled="readonly || !editing" />
                        </td>
                        <td 
                            class="font-mono text-blue-800 px-4 py-2" 
                            :class="{ 'cursor-pointer': gene.details.length > 0, 'cursor-default': gene.details.length === 0 }"
                            @click="gene.details.length > 0 && toggleExpanded(index)">
                            {{ gene.gene_symbol }}
                        </td>
                        <td 
                            class="px-4 py-2" 
                            :class="{ 'cursor-pointer': gene.details.length > 0, 'cursor-default': gene.details.length === 0 }"
                            @click="gene.details.length > 0 && toggleExpanded(index)">
                            {{ gene.statuses.join(', ') }} ({{ gene.details.length }})
                        </td>
                        <td class="px-4 py-2">
                            <template v-if="!readonly && editing">
                                <select v-model="gene.tier" class="border rounded px-1 py-0.5" @change="updateTier(gene)" :disabled="savingTierFor === gene.id || readonly || !editing">
                                    <option value="">—</option>
                                    <option value="1">Tier 1</option>
                                    <option value="2">Tier 2</option>
                                    <option value="3">Tier 3</option>
                                    <option value="4">Tier 4</option>
                                </select>
                                <span v-if="savingTierFor === gene.id" class="ml-2 text-gray-500">Saving...</span>
                            </template>
                            <span v-else>{{ gene.tier || '—' }}</span>
                        </td>
                    </tr>

                    <!-- Expanded details -->
                    <tr v-if="expanded.includes(index)">
                        <td colspan="4" class="bg-gray-100 px-6 py-3 text-sm">
                            <div v-for="entry in gene.details" :key="entry.disease + entry.current_status" class="mb-2">
                                <div class="grid grid-cols-2 text-sm text-gray-700">
                                    <div><strong>Status:</strong> {{ entry.current_status }}</div>
                                    <div><strong>Status Date:</strong> {{ entry.current_status_date }}</div>
                                    <div><strong>Disease:</strong> {{ entry.mondo_id }} {{ entry.disease }}</div>
                                    <div><strong>Expert Panel:</strong> {{ entry.expert_panel }}</div>
                                    <div><strong>Classification:</strong> {{ entry.classification }}</div>
                                    <div><strong>MOI:</strong> {{ entry.moi }}</div>
                                    <div><strong>Type:</strong> {{ entry.curation_type }}</div>
                                    <div><strong>Phenotype:</strong> {{ entry.phenotype }}</div>
                                </div>
                                <hr />
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center items-center gap-2 mt-4">
        <button @click="currentPage--" :disabled="currentPage === 1" class="btn">Prev</button>
        <span class="text-sm">Page {{ currentPage }} / {{ totalPages }}</span>
        <button @click="currentPage++" :disabled="currentPage === totalPages" class="btn">Next</button>
    </div>
</div>
</template>
