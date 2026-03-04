<template>
  <div>
    <!-- Header controls -->
    <div class="flex justify-between items-center mb-4">
      <div v-if="canEdit && editing" class="flex items-center gap-2">
        <button class="btn blue" @click="startAdd" :disabled="isFormVisible">
          + Add Gene
        </button>
      </div>

      <div class="flex items-center gap-4">
        <!-- Search -->
        <input
          v-model="search"
          type="text"
          placeholder="Search genes, diseases..."
          class="border rounded px-2 py-1 text-sm"
        />
      </div>
    </div>

    <!-- Add/Edit Form -->
    <transition name="fade" @after-enter="onFormEntered">
      <div v-if="isFormVisible" ref="formEl" class="border rounded bg-gray-50 p-4 mb-4">
        <h3 class="text-lg font-semibold mb-3">
          {{ isEditingId ? 'Edit Gene' : 'Add Gene' }}
        </h3>

        <div class="border rounded bg-white p-3 mt-3">
          <div class="grid grid-cols-2 gap-2">
            <!-- Gene -->
            <div>
              <label class="block text-sm font-medium mb-1">Gene *</label>
              <GeneSearchSelect v-model="formGene.gene" placeholder="Search gene..." />
              <p v-if="errors.gene" class="text-red-500 text-xs mt-1">{{ errors.gene }}</p>
            </div>

            <!-- Disease -->
            <div>
              <label class="block text-sm font-medium mb-1">Disease</label>
              <DiseaseSearchSelect v-model="formGene.disease" doid="true" placeholder="Search disease..." />
              <p v-if="errors.disease" class="text-red-500 text-xs mt-1">{{ errors.disease }}</p>
            </div>
          </div>
        </div>

        <!-- Buttons -->
        <div class="mt-4 flex justify-end gap-2">
          <button class="btn btn-gray" @click="cancelForm">Cancel</button>
          <button class="btn blue" :disabled="!isFormValid" @click="saveForm">
            Save
          </button>
        </div>
      </div>
    </transition>

    <!-- Bulk Tier Update Bar -->
    <div
      v-if="selectedGenes.length > 0 && !readonly"
      class="flex items-center gap-2 bg-gray-50 p-3 border rounded mb-3"
    >
      {{ selectedGenes.length }} gene(s) selected.
      <span class="font-semibold">Bulk Tier Update:</span>
      <select v-model="bulkTier" class="border rounded px-2 py-1 text-sm">
        <option value="">Select Tier</option>
        <option value="1">Current</option>
        <option value="2">Future</option>
      </select>
      <button
        class="bg-blue-600 text-white px-3 py-1 rounded disabled:opacity-50"
        @click="applyBulkTier"
        :disabled="!bulkTier || selectedGenes.length === 0"
      >
        Apply
      </button>
      <button
        type="button"
        class="border rounded px-3 py-1 text-sm bg-white hover:bg-gray-50"
        @click="clearSelection"
        title="Clear selection"
      >
        Clear
      </button>
    </div>

    <!-- List toolbar -->
    <div class="mb-3 flex items-center justify-between border rounded-lg bg-white px-3 py-2">
      <div class="flex items-center gap-3">
        <input
          v-if="canEdit && editing"
          type="checkbox"
          :checked="isAllSelected"
          @change="toggleSelectAll"
          aria-label="Select all on page"
        />
        <span class="text-sm text-gray-600">
          {{ paginatedGenes.length }} shown / {{ filteredAndSortedGenes.length }} total
        </span>
      </div>

      <div class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Sort by</label>
        <select v-model="sortKey" class="border rounded px-2 py-1 text-sm">
          <option value="gene_symbol">HGNC Symbol</option>
          <option value="disease">Disease</option>
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

        <div class="ml-2 flex items-center gap-2">
          <label class="text-xs text-gray-500">Page size</label>
          <select v-model="pageSize" class="border rounded px-2 py-1 text-sm">
            <option v-for="size in [20, 50, 100]" :key="size" :value="size">{{ size }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Card list -->
    <div v-if="paginatedGenes.length === 0" class="p-6 text-center text-sm text-gray-500 border rounded-lg bg-white">
      No genes found.
    </div>

    <ul v-else class="space-y-2">
      <li v-for="gene in paginatedGenes" :key="gene.id">
        <div class="rounded-2xl overflow-hidden border border-gray-200 bg-white shadow-sm hover:shadow transition-shadow">
          <div class="flex items-start justify-between gap-3 p-3">
            <div class="flex items-start gap-3">
              <div v-if="canEdit && editing" class="mt-1">
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
                  <span
                    v-if="gene.mondo_id"
                    class="text-xs rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
                  >
                    {{ gene.mondo_id }} {{ gene.disease_name || '—' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <template v-if="editing">
                <select
                  v-model="gene.tier"
                  class="border rounded px-2 py-1 text-xs"
                  @change="updateTier(gene)"
                  :disabled="savingTierFor === gene.id || readonly"
                  title="Tier"
                >
                  <option :value="null">—</option>
                  <option value="1">Current</option>
                  <option value="2">Future</option>
                </select>

                <dropdown-menu v-if="!gene.toDelete" :hide-cheveron="true" class="relative">
                  <template #label>
                    <button class="rounded border px-2 py-1 text-xs bg-white">⋯</button>
                  </template>
                  <dropdown-item @click="startEdit(gene)">Edit</dropdown-item>
                  <dropdown-item @click="confirmRemove(gene)">Remove</dropdown-item>
                </dropdown-menu>
              </template>

              <span v-else class="text-xs text-gray-700">Tier: {{ gene.tier || '—' }}</span>
            </div>
          </div>
        </div>
      </li>
    </ul>

    <!-- Pagination -->
    <div class="mt-4 flex justify-between items-center">
      <button class="btn btn-gray" :disabled="currentPage === 1" @click="currentPage--">Prev</button>
      <span>Page {{ currentPage }} of {{ totalPages }}</span>
      <button class="btn btn-gray" :disabled="currentPage === totalPages" @click="currentPage++">Next</button>
    </div>

    <!-- Remove Confirmation Modal -->
    <div v-if="showConfirmRemove" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h3 class="text-lg font-semibold mb-4">Confirm Removal</h3>
        <p class="mb-6">
          Are you sure you want to remove <strong>{{ geneToRemove?.gene_symbol }}</strong>?
        </p>
        <div class="flex justify-end gap-2">
          <button class="btn btn-gray" @click="cancelRemove">Cancel</button>
          <button class="btn btn-red" @click="removeGene">Remove</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '@/http'
import { ref, nextTick, computed, onMounted, watch } from 'vue'
import { useStore } from 'vuex'
import GeneSearchSelect from '@/components/forms/GeneSearchSelect.vue'
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect.vue'
import { hasAnyPermission } from '@/auth_utils'

export default {
  name: 'ScvcepGeneList',
  components: { GeneSearchSelect, DiseaseSearchSelect },
  props: {
    readonly: { type: Boolean, required: false, default: false },
    editing: { type: Boolean, required: false, default: true },
  },
  emits: ['saved'],
  setup(props, { emit }) {
    const store = useStore()

    const genes = ref([])

    // form
    const isFormVisible = ref(false)
    const isEditingId = ref(null)
    const formGene = ref({ gene: null, disease: null })
    const errors = ref({})

    // scroll-to-form
    const formEl = ref(null)
    const shouldScrollToForm = ref(false)

    // selection + bulk tier
    const selectedGenes = ref([])
    const bulkTier = ref('')
    const savingTierFor = ref(null)

    // list controls
    const search = ref('')
    const sortKey = ref('gene_symbol')
    const sortOrder = ref('asc')
    const currentPage = ref(1)
    const pageSize = ref(20)

    // remove modal
    const showConfirmRemove = ref(false)
    const geneToRemove = ref(null)

    const group = computed(() => store.getters['groups/currentItemOrNew'])
    const canEdit = computed(
      () => hasAnyPermission(['ep-applications-manage', ['application-edit', group.value]]) && !props.readonly
    )

    const isFormValid = computed(() => {
      return !!formGene.value?.gene?.hgnc_id
    })

    const getGenes = async () => {
      if (!group.value?.uuid) return
      try {
        await store.dispatch('groups/getGenes', group.value)
        genes.value = group.value.expert_panel.genes || []
      } catch (error) {
        store.commit('pushError', error.response?.data || error)
      }
    }

    const startAdd = () => {
      errors.value = {}
      isEditingId.value = null
      formGene.value = { gene: null, disease: null }
      isFormVisible.value = true
      shouldScrollToForm.value = true
    }

    const startEdit = (gene) => {
      errors.value = {}
      isEditingId.value = gene.id
      formGene.value = {
        gene: gene.hgnc_id ? { hgnc_id: gene.hgnc_id, gene_symbol: gene.gene_symbol } : null,
        disease: gene.mondo_id ? { mondo_id: gene.mondo_id, name: gene.disease_name } : null,
      }
      const wasOpen = isFormVisible.value
      isFormVisible.value = true
      if (wasOpen) scrollToFormSoon()
      else shouldScrollToForm.value = true
    }

    const cancelForm = () => {
      isFormVisible.value = false
      isEditingId.value = null
      formGene.value = { gene: null, disease: null }
      errors.value = {}
    }

    const buildPayload = () => {
      const gene = formGene.value.gene
      const disease = formGene.value.disease

      // NOTE:
      // If your API still requires legacy fields (moi/plan/etc), add them here with null/defaults.
      return {
        hgnc_id: gene?.hgnc_id ?? null,
        gene_symbol: gene?.gene_symbol ?? '',
        mondo_id: disease?.mondo_id ?? '',
        disease_name: disease?.name ?? '',
      }
    }

    const saveForm = async () => {
      errors.value = {}
      const payload = buildPayload()

      try {
        if (isEditingId.value) {
          await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/${isEditingId.value}`, payload)
          store.commit('pushSuccess', `Successfully updated ${payload.gene_symbol}`)
        } else {
          await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, { genes: [payload] })
          store.commit('pushSuccess', `Successfully added ${payload.gene_symbol}`)
        }

        await getGenes()
        cancelForm()
        emit('saved')
      } catch (error) {
        const res = error?.response
        if (res?.status === 422 && res.data?.errors) {
          // map common keys to user-friendly form errors
          const e = res.data.errors
          errors.value = {
            gene: e.hgnc_id?.[0] || e.gene_symbol?.[0] || null,
            disease: e.mondo_id?.[0] || e.disease_name?.[0] || null,
          }
          const all = Object.values(e).flat()
          store.commit('pushError', all.join('\n'))
        } else {
          store.commit('pushError', res?.data?.message || `Failed to save gene`)
        }
      }
    }

    // remove flow
    const confirmRemove = (gene) => {
      geneToRemove.value = gene
      showConfirmRemove.value = true
    }

    const cancelRemove = () => {
      geneToRemove.value = null
      showConfirmRemove.value = false
    }

    const removeGene = async () => {
      if (!geneToRemove.value) return
      try {
        await api.delete(`/api/groups/${group.value.uuid}/expert-panel/genes/${geneToRemove.value.id}`)
        store.commit('pushSuccess', `Successfully removed gene ${geneToRemove.value.gene_symbol}`)
        cancelRemove()
        await getGenes()
      } catch (error) {
        store.commit('pushError', error.response?.data || error)
      }
    }

    // tier update
    const updateTier = async (gene) => {
      savingTierFor.value = gene.id
      const oldTier = gene.tier
      try {
        await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/update-tier`, {
          ids: [gene.id],
          tier: gene.tier || null,
        })
        store.commit('pushSuccess', `Tier updated for ${gene.gene_symbol}`)
      } catch (error) {
        gene.tier = oldTier
        store.commit('pushError', `Failed to update tier`)
      } finally {
        savingTierFor.value = null
      }
    }

    const applyBulkTier = async () => {
      if (!bulkTier.value || selectedGenes.value.length === 0) return
      try {
        await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/update-tier`, {
          ids: selectedGenes.value,
          tier: bulkTier.value,
        })
        store.commit('pushSuccess', `Tier updated for ${selectedGenes.value.length} genes`)
        selectedGenes.value = []
        bulkTier.value = ''
        await getGenes()
      } catch (error) {
        store.commit('pushError', 'Failed to update tiers in bulk')
      }
    }

    // selection
    const clearSelection = () => (selectedGenes.value = [])
    const toggleSelect = (id) => {
      selectedGenes.value = selectedGenes.value.includes(id)
        ? selectedGenes.value.filter((x) => x !== id)
        : [...selectedGenes.value, id]
    }

    const toggleSelectAll = () => {
      const idsOnPage = paginatedGenes.value.map((g) => g.id)
      const allSelected = idsOnPage.length > 0 && idsOnPage.every((id) => selectedGenes.value.includes(id))
      selectedGenes.value = allSelected
        ? selectedGenes.value.filter((id) => !idsOnPage.includes(id))
        : [...new Set([...selectedGenes.value, ...idsOnPage])]
    }

    // filtering + sorting (gene/disease/tier only)
    const filteredAndSortedGenes = computed(() => {
      let result = [...genes.value]
      const keyword = search.value.trim().toLowerCase()

      if (keyword) {
        result = result.filter((g) =>
          (g.gene_symbol || '').toLowerCase().includes(keyword) ||
          (g.mondo_id || '').toLowerCase().includes(keyword) ||
          (g.disease_name || '').toLowerCase().includes(keyword)
        )
      }

      result.sort((a, b) => {
        if (sortKey.value === 'tier') {
          const av = a.tier ? Number(a.tier) : 0
          const bv = b.tier ? Number(b.tier) : 0
          return sortOrder.value === 'asc' ? av - bv : bv - av
        }

        const aVal =
          sortKey.value === 'disease'
            ? `${a.mondo_id || ''} ${a.disease_name || ''}`.toLowerCase()
            : (a.gene_symbol || '').toLowerCase()

        const bVal =
          sortKey.value === 'disease'
            ? `${b.mondo_id || ''} ${b.disease_name || ''}`.toLowerCase()
            : (b.gene_symbol || '').toLowerCase()

        return sortOrder.value === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
      })

      return result
    })

    const totalPages = computed(() => Math.max(1, Math.ceil(filteredAndSortedGenes.value.length / pageSize.value)))

    const paginatedGenes = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      return filteredAndSortedGenes.value.slice(start, start + pageSize.value)
    })

    const isAllSelected = computed(() => {
      if (paginatedGenes.value.length === 0) return false
      return paginatedGenes.value.every((g) => selectedGenes.value.includes(g.id))
    })

    // keep paging sane
    watch([search, pageSize], () => {
      currentPage.value = 1
    })

    watch(totalPages, (tp) => {
      if (currentPage.value > tp) currentPage.value = tp
    })

    // scroll-to-form helpers
    const scrollToForm = () => {
      const el = formEl.value
      if (!el) return
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }

    const onFormEntered = () => {
      if (shouldScrollToForm.value) {
        shouldScrollToForm.value = false
        scrollToForm()
      }
    }

    const scrollToFormSoon = async () => {
      await nextTick()
      requestAnimationFrame(() => scrollToForm())
    }

    onMounted(() => {
      getGenes()
    })

    return {
      // permissions + props
      group,
      canEdit,

      // data
      genes,
      search,
      sortKey,
      sortOrder,
      currentPage,
      pageSize,

      // form
      isFormVisible,
      isEditingId,
      formGene,
      errors,
      isFormValid,
      startAdd,
      startEdit,
      cancelForm,
      saveForm,

      // list
      filteredAndSortedGenes,
      paginatedGenes,
      totalPages,

      // tier + selection
      selectedGenes,
      bulkTier,
      savingTierFor,
      isAllSelected,
      toggleSelect,
      toggleSelectAll,
      clearSelection,
      updateTier,
      applyBulkTier,

      // remove modal
      showConfirmRemove,
      geneToRemove,
      confirmRemove,
      cancelRemove,
      removeGene,

      // scrolling
      formEl,
      onFormEntered,
    }
  },
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 500;
  border-radius: 4px;
  padding: 6px 12px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}
.btn.blue {
  background-color: #2563eb;
  color: white;
}
.btn.blue:hover {
  background-color: #1d4ed8;
}
.btn-gray {
  background-color: #f3f4f6;
  color: #374151;
}
.btn-gray:hover {
  background-color: #e5e7eb;
}
.btn-red {
  background-color: #dc2626;
  color: white;
}
.btn-red:hover {
  background-color: #b91c1c;
}

input[type='text'],
select {
  border: 1px solid #d1d5db;
  border-radius: 4px;
  padding: 4px 8px;
  font-size: 14px;
}
input:focus,
select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 1px #2563eb;
}
</style>
