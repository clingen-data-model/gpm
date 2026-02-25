<template>
<div>
    <!-- Header controls -->
    <div class="flex justify-between items-center mb-4">
        <div v-if="editing" class="flex items-center gap-2">
            <button class="btn blue" @click="startAdd" :disabled="isFormVisible">
                + Add Gene
            </button>
        </div>

        <div class="flex items-center gap-4">
            <!-- Search -->
            <input
                v-model="search"
                type="text"
                placeholder="Search genes, diseases, MOI, notes..."
                class="border rounded px-2 py-1 text-sm"
            />

            <!-- MOI Filter -->
            <select v-model="filterMoi" class="border p-1 rounded text-sm">
                <option value="">All MOIs</option>
                <option v-for="moi in moiOptions" :key="moi" :value="moi">{{ moi }}</option>
            </select>

            <!-- Classification Filter -->
            <select v-model="filterClassification" class="border p-1 rounded text-sm">
                <option value="">All Classifications</option>
                <option v-for="c in classificationOptions" :key="c" :value="c">{{ c }}</option>
            </select>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <transition name="fade" @after-enter="onFormEntered">
        <div v-if="isFormVisible" ref="formEl" class="border rounded bg-gray-50 p-4 mb-4">
            <h3 class="text-lg font-semibold mb-3">
                {{ isEditing ? 'Edit Gene' : 'Add Gene' }}
            </h3>

            <div v-if="!formGene?.is_other" class="space-y-2">
                <label class="block font-semibold">Select Gene-Disease-MOI:</label>
                <CuratedGeneSearchSelect
                    v-model="formGene"
                    @update:model-value="handleCuratedSelection"
                    placeholder="Search curated Gene-Disease-MOI"
                    class="w-full"
                    :key="curatedGeneKey"
                />
                <button
                    type="button"
                    class="text-blue-600 underline text-sm hover:text-blue-800"
                    @click="selectOther"
                >
                    Click here for <strong>Other (Not Curated)</strong>
                </button>

                <!-- Curated plan required for Moderate/Limited -->
                <div
                    v-if="formGene?.requires_plan"
                    class="mt-3 border rounded bg-amber-50 p-3"
                    >
                    <div class="flex items-start gap-2 mb-2">
                        <span class="text-orange-500 font-bold">⚠</span>
                        <div class="text-sm text-gray-800">
                        This gene is currently classified as <strong>{{ (formGene.classification ?? formGene.plan?.classification) }}</strong>.  
                        Please explain the rationale for proposing its inclusion.
                        </div>
                    </div>

                    <label class="block text-sm font-medium mb-1">Plan for Curation *</label>
                    <RichTextEditor
                        v-model="formGene.curated_plan_text"
                        placeholder="Describe your plan (min 20 characters)"
                        class="w-full"
                    />
                    <p v-if="!isCuratedPlanValid" class="text-xs text-red-500 mt-1">
                        Plan must be at least 20 characters.
                    </p>
                </div>
            </div>

            <!-- Manual entry if Other -->
            <div v-if="formGene && formGene.is_other" class="border rounded bg-white p-3 mt-3">
                <h5 class="text-lg font-semibold mb-2">Provide details for non-curated gene</h5>
                <div class="grid grid-cols-2 gap-2">
                    <!-- Gene -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Gene *</label>
                        <GeneSearchSelect v-model="formGene.custom_gene" placeholder="Search gene..." />
                        <p v-if="errors['custom_gene']" class="text-red-500 text-xs mt-1">{{ errors['custom_gene'][0] }}</p>
                    </div>

                    <!-- MOI -->
                    <div>
                        <label class="block text-sm font-medium mb-1">MOI</label>
                        <select v-model="formGene.custom_moi" class="form-select w-full">
                            <option value="">----Select MOI-----</option>
                            <option v-for="moi in mois" :key="moi.id" :value="moi">{{ moi.name }} ({{ moi.abbreviation }})</option>
                        </select>
                        <p v-if="errors['custom_moi']" class="text-red-500 text-xs mt-1">{{ errors['custom_moi'][0] }}</p>
                    </div>

                    <!-- Disease -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Disease</label>
                        <DiseaseSearchSelect v-model="formGene.custom_disease" placeholder="Search disease..." />
                    </div>

                    <!-- Plan -->
                    <div class="col-span-2 mb-6">
                        <label class="block text-sm font-medium mb-1">Plan for Curation *</label>
                        <RichTextEditor
                            v-model="formGene.custom_plan"
                            placeholder="Describe your plan (min 20 characters)"
                            class="w-full"
                        />
                        <p v-if="errors['custom_plan']" class="text-red-500 text-xs mt-1">{{ errors['custom_plan'][0] }}</p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-4 flex justify-end gap-2">
                <button class="btn btn-gray" @click="cancelForm">Cancel</button>                
                <button
                    class="btn blue"
                    :disabled="isEditing ? false : (
                        (formGene?.is_other && !isOtherFormValid) ||
                        (!formGene?.is_other && !isCuratedPlanValid)
                    )"
                    @click="saveForm"
                    >
                    Save
                </button>
            </div>
        </div>
    </transition>

    <!-- Bulk Tier Update Bar -->
    <div v-if="selectedGenes.length > 0 && !readonly" class="flex items-center gap-2 bg-gray-50 p-3 border rounded mb-3">
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
        <button type="button" class="border rounded px-3 py-1 text-sm bg-white hover:bg-gray-50" @click="clearSelection" title="Clear selection">
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
                <option value="moi">MOI</option>
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
                    <option v-for="size in [2, 20, 50, 100]" :key="size" :value="size">{{ size }}</option>
                </select>
            </div>
        </div>
    </div>


    <!-- Card list (replaces the whole <table>...</table>) -->
    <div v-if="paginatedGenes.length === 0" class="p-6 text-center text-sm text-gray-500 border rounded-lg bg-white">
        No genes found.
    </div>

    <ul v-else class="space-y-2">
        <li v-for="(gene, index) in paginatedGenes" :key="gene.id">
            <div class="rounded-2xl overflow-hidden border border-gray-200 bg-white shadow-sm hover:shadow transition-shadow">
                
                <div class="flex items-start justify-between gap-3 p-3">
                    <div class="flex items-start gap-3">
                        <div v-if="canEdit && editing" class="mt-1">
                            <input type="checkbox" :checked="selectedGenes.includes(gene.id)" @change="toggleSelect(gene.id)" :aria-label="`Select ${gene.gene_symbol}`" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-base font-semibold text-gray-900">{{ gene.gene_symbol }}</span>
                                <span v-if="gene.mondo_id" class="text-xs rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">{{ gene.mondo_id }}</span>
                                <span v-if="gene.moi" class="text-xs rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">{{ gene.moi }}</span>
                                <span v-if="gene.plan?.is_other" class="'inline-flex h-2 w-2 rounded-full animate-pulse bg-rose-500" aria-hidden="true">

                                </span>
                                <span
                                    v-if="gene.is_outdated && gene.gt_data"
                                    class="text-[11px] rounded-full bg-amber-100 text-amber-900 px-2 py-0.5 border border-amber-300"
                                >Snapshot out of date</span>
                            </div>
                            <div class="mt-0.5 text-sm text-gray-700 truncate">{{ gene.disease_name }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <template v-if="editing">
                            <select v-model="gene.tier" class="border rounded px-2 py-1 text-xs" @change="updateTier(gene)" :disabled="savingTierFor === gene.id || readonly" title="Tier">
                                <option value="null">—</option>
                                <option value="1">Current</option>
                                <option value="2">Future</option>
                            </select>

                            <button v-if="gene.is_outdated && gene.gt_data" @click="applyGtUpdate(gene)" title="Refresh from latest GT"
                            class="hidden sm:inline-flex rounded border border-amber-400 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                                Apply latest GT changes ⚠
                            </button>

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

                <!-- Snapshot quick facts (COMPACT) -->
                <div  v-if="gene.plan" class="px-3 pb-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 text-sm">
                    <div v-if="gene.plan?.is_other" class="sm:col-span-2 lg:col-span-4 rounded-xl border p-2 text-sm border-rose-300 bg-rose-50 p-2">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Plan</div>
                        <div class="prose max-w-none" v-html="htmlFromMarkdown((gene.plan?.the_plan || '').replace(/\\/g, ''))"></div>
                    </div>
                    <template v-else>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-gray-500">Expert Panel</div>
                            <div class="text-sm text-gray-900">{{ gene.plan?.expert_panel ?? 'N/A' }}</div>
                        </div>

                        <div :class="['rounded-xl border p-2',
                            ['Moderate','Limited'].includes(gene.plan?.classification)
                            ? (gene.plan?.classification === 'Limited'
                                ? 'border-amber-200 bg-amber-100 ring-1 ring-amber-500'
                                : 'border-amber-100 bg-amber-50 ring-1 ring-amber-300')
                            : 'border-gray-200 bg-gray-50'
                        ]">
                            <div class="text-[11px] uppercase tracking-wide text-gray-500">Classification</div>
                            <div class="mt-0.5 text-gray-900 flex items-center gap-2 text-sm">
                                {{ gene.plan?.classification ?? 'N/A' }}
                                <span v-if="['Moderate','Limited'].includes(gene.plan?.classification)" 
                                    :class="['inline-flex h-2 w-2 rounded-full animate-pulse', gene.plan?.classification === 'Limited' ? 
                                        'bg-amber-500' : 'bg-amber-300']" aria-hidden="true"></span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-gray-500">Status</div>
                            <div class="text-sm text-gray-900">{{ gene.plan?.curation_status ?? 'N/A' }}</div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-gray-500">Date Approved</div>
                            <div class="text-sm text-gray-900">
                                {{ gene.plan?.date_approved ? new Date(gene.plan?.date_approved).toLocaleDateString() : 'N/A' }}
                            </div>
                        </div>

                        <div v-if="gene.plan?.phenotypes" class="sm:col-span-2 lg:col-span-4 rounded-xl border border-gray-200 bg-gray-50 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-gray-500">Phenotypes</div>
                            <div class="text-sm text-gray-900 line-clamp-2">{{ gene.plan?.phenotypes ?? 'N/A' }}</div>
                        </div>
                        
                        <div v-if="['Moderate','Limited'].includes(gene.plan?.classification) && gene.plan?.curated_plan_text" class="sm:col-span-2 lg:col-span-4 rounded-xl border border-amber-200 bg-amber-50 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-amber-800">Plan</div>
                            <div class="prose max-w-none text-sm text-amber-900" v-html="htmlFromMarkdown((gene.plan?.curated_plan_text || '').replace(/\\/g, ''))"></div>
                        </div>
                    </template>
                </div>

                <div v-if="gene.is_outdated && gene.gt_data" class="flex items-center justify-between gap-2 border-t border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-4 w-4">⚠</span>
                        <span class="truncate">Snapshot differs from latest GeneTracker data.</span>
                    </div>
                    <button @click="toggleExpanded(index)" class="rounded border border-amber-400 px-2 py-1 text-xs font-medium hover:bg-amber-100">
                        {{ expanded.includes(index) ? 'Hide GT diff' : 'Show GT diff' }}
                    </button>
                </div>
                
                <transition name="fade">
                    <div v-if="gene.is_outdated && gene.gt_data && expanded.includes(index)" class="px-3 pb-3 pt-2 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div v-for="row in compactDiffRows(gene)" :key="row.key" class="rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between border-b bg-gray-50 px-2 py-1 text-[11px] text-gray-600">
                                <span class="uppercase tracking-wide">{{ row.label }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <div class="min-w-0 p-2">
                                <div class="truncate" :title="row.a">{{ row.a || '—' }}</div>
                                </div>
                                <div class="min-w-0 p-2" :class="isDiff(row.a, row.b) ? 'bg-sky-50 ring-1 ring-sky-300' : ''">
                                <div class="truncate" :title="row.b">{{ row.b || '—' }}</div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Small-screen actions -->
                        <div class="mt-2 flex flex-wrap items-center gap-2 sm:hidden">
                            <button v-if="gene.is_outdated && gene.gt_data" type="button" class="rounded bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700" @click="applyGtUpdate(gene)">
                                Refresh from GT
                            </button>
                            <button type="button" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-800 hover:bg-gray-50" @click="toggleExpanded(index)" >
                                Keep Snapshot
                            </button>
                        </div>
                    </div>
                </transition>
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
            <p class="mb-6">Are you sure you want to remove <strong>{{ selectedGene?.gene_symbol }}</strong>?</p>
            <div class="flex justify-end gap-2">
                <button class="btn btn-gray" @click="cancelRemove">Cancel</button>
                <button class="btn btn-red" @click="removeGene">Remove</button>
            </div>
        </div>
    </div>
</div>
</template>

<script>
import { api } from '@/http';
import { ref, nextTick, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import GeneSearchSelect from '@/components/forms/GeneSearchSelect.vue';
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect.vue';
import CuratedGeneSearchSelect from '@/components/forms/CuratedGeneSearchSelect.vue';
import { htmlFromMarkdown, markdownFromHTML } from '@/markdown-utils'
import { hasAnyPermission } from '@/auth_utils';
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue';

export default {
    name: 'VcepGeneList',
    components: { GeneSearchSelect, DiseaseSearchSelect, CuratedGeneSearchSelect, RichTextEditor },
    props: { readonly: { type: Boolean, required: false, default: false }, editing: { type: Boolean, required: false, default: true } },
    emits: ['saved'],
    setup(props, { emit }) {
        const store = useStore();
        const genes = ref([]);
        const formGene = ref(null);
        const isEditing = ref(false);
        const isFormVisible = ref(false);
        const curatedGeneKey = ref(0);
        const errors = ref({});
        const mois = ref([]);

        const formEl = ref(null)
        const shouldScrollToForm = ref(false)

        const selectedGenes = ref([]);
        const bulkTier = ref('');
        const savingTierFor = ref(null);
        
        const search = ref('');
        const filterMoi = ref('');
        const filterClassification = ref('');
        const sortKey = ref('gene_symbol');
        const sortOrder = ref('asc');

        const currentPage = ref(1);
        const pageSize = ref(20);

        const group = computed(() => store.getters['groups/currentItemOrNew']);

        const canEdit = computed(() => hasAnyPermission(['ep-applications-manage', ['application-edit', group.value]]) && !props.readonly);

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
                await api.delete(`/api/groups/${group.value.uuid}/expert-panel/genes/${selectedGene.value.id}`);
                showConfirmRemove.value = false;
                store.commit('pushSuccess', `Successfully removed gene ${selectedGene.value.gene_symbol}`);
                selectedGene.value = null;
                await getGenes();                
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
        };


        const getMois = async () => {
            try {
                const response = await api.get('/api/mois');
                mois.value = response.data;
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
        };

        const getGenes = async () => {
            if (!group.value.uuid) return;
            try {
                await store.dispatch('groups/getGenes', group.value);
                genes.value = group.value.expert_panel.genes;
            } catch (error) {
                store.commit('pushError', error.response?.data || error);
            }
        };

        const setSort = key => {
            if (sortKey.value === key) {
                sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
            } else {
                sortKey.value = key;
                sortOrder.value = 'asc';
            }
        };

        const startAdd = () => {
            isFormVisible.value = true;
            isEditing.value = false;
            formGene.value = null;
            curatedGeneKey.value++;
        };

        const unescapeMarkdown = (s = '') => s.replace(/\\([\\`*_{}\[\]()#+\-.!>~|])/g, '$1');

        const startEdit = (gene) => {
            const wasOpen = isFormVisible.value
            isFormVisible.value = true;
            isEditing.value = gene.id;

            if (gene.plan?.is_other) {
                // Build form for Other gene
                formGene.value = {
                    id: gene.id,
                    is_other: true,
                    custom_gene: { hgnc_id: gene.hgnc_id, gene_symbol: gene.gene_symbol },
                    custom_disease: { mondo_id: gene.mondo_id, name: gene.disease_name },
                    custom_moi: mois.value.find(m => m.abbreviation === gene.moi) || null,
                    custom_plan: (unescapeMarkdown(gene.plan?.the_plan || ''))
                };
            } else {
                const plan = {
                    ...gene.plan,
                    is_other: false,
                    curated_plan_text: gene.plan?.curated_plan_text ?? ''
                };

                formGene.value = {
                    id: gene.id,
                    hgnc_id: gene.hgnc_id,
                    gene_symbol: gene.gene_symbol,
                    disease_name: gene.disease_name,
                    mondo_id: gene.mondo_id,
                    moi: gene.moi,
                    date_approved: gene.date_approved,
                    requires_plan: ['Moderate', 'Limited'].includes(gene.plan?.classification),
                    plan,
                    curated_plan_text: (unescapeMarkdown(plan?.curated_plan_text || '')),
                    is_other: false
                };
            }
            console.log('Editing gene:', formGene.value);
            curatedGeneKey.value++;
            if (wasOpen) {
                scrollToFormSoon()
            } else {
                shouldScrollToForm.value = true
            }
        };


        const cancelForm = () => {
            isFormVisible.value = false;
            isEditing.value = false;
            formGene.value = null;
            errors.value = {};
        };

        const isOtherFormValid = computed(() => {
            if (!formGene.value?.is_other) return false;
            return (
                !!formGene.value.custom_gene?.hgnc_id &&
                // !!formGene.value.custom_disease?.mondo_id &&
                (formGene.value.custom_plan?.trim().length >= 20)
            );
        });

        const selectOther = () => {
            const originalId = formGene.value?.id || null;
            formGene.value = {
                id: originalId,
                is_other: true,
                custom_gene: null,
                custom_disease: null,
                custom_moi: null,
                custom_plan: ''
            };
        };

        const handleCuratedSelection = selected => {
            if (selected && !selected.is_other) {
                formGene.value = {
                    ...selected,
                    is_other: false,
                    requires_plan: ['Moderate', 'Limited'].includes(selected.classification),
                    curated_plan_text: htmlFromMarkdown('')
                };

            }
        };

        const isCuratedPlanValid = computed(() => {
            if (!formGene.value?.requires_plan) return true;
            const plain = (formGene.value.curated_plan_text || '')
                .replace(/<[^>]+>/g, '') // strip HTML from RTE
                .trim();
            return plain.length >= 20;
        });

        // helper to build a complete, flat snapshot for plan
        const buildBasePlan = (fg) => {
            const fromPlan = (fg?.plan && Object.keys(fg.plan).length) ? fg.plan : {};

            const snap = {
                // prefer what's already inside plan; fall back to top-level fields on fg
                curation_id:       fromPlan.curation_id       ?? fg.curation_id,
                gene_symbol:       fromPlan.gene_symbol       ?? fg.gene_symbol,
                hgnc_id:           fromPlan.hgnc_id           ?? fg.hgnc_id,
                hgnc_name:         fromPlan.hgnc_name         ?? fg.hgnc_name ?? null,
                disease_name:      fromPlan.disease_name      ?? fg.disease_name,
                mondo_id:          fromPlan.mondo_id          ?? fg.mondo_id,
                expert_panel:      fromPlan.expert_panel      ?? fg.expert_panel ?? null,
                moi:               fromPlan.moi               ?? fg.moi,
                moi_name:          fromPlan.moi_name          ?? fg.moi_name ?? null,
                hp_id:             fromPlan.hp_id             ?? fg.hp_id ?? null,
                classification_id: fromPlan.classification_id ?? fg.classification_id ?? null,
                classification:    fromPlan.classification    ?? fg.classification ?? null,
                curation_status_id:fromPlan.curation_status_id?? fg.curation_status_id ?? null,
                curation_type:     fromPlan.curation_type     ?? fg.curation_type ?? null,
                curation_status:   fromPlan.curation_status   ?? fg.curation_status ?? null,
                date_approved:     fromPlan.date_approved     ?? fg.date_approved ?? null,
                phenotypes:        fromPlan.phenotypes        ?? fg.phenotypes ?? null,
                phenotypeIDs:      fromPlan.phenotypeIDs      ?? fg.phenotypeIDs ?? null,
                checkKey:          fromPlan.checkKey          ?? fg.checkKey ?? null,

                // normalize flags/text
                is_other: false,
                requires_plan: !!fg?.requires_plan,
                curated_plan_text: (fg?.requires_plan ? markdownFromHTML(fg?.curated_plan_text || fromPlan.curated_plan_text || '') : markdownFromHTML(fromPlan.curated_plan_text || ''))
            };

            // strip undefined to keep payload clean
            Object.keys(snap).forEach(k => snap[k] === undefined && delete snap[k]);
            return snap;
        };

        const saveForm = async () => {
            try {
                let payload;
                if (formGene.value.is_other) {
                    const plan = {
                        is_other: true,
                        the_plan: markdownFromHTML(formGene.value.custom_plan),
                        moi_name: formGene.value.custom_moi?.name || '',
                        hp_id: formGene.value.custom_moi?.hp_id || ''
                    };
                    payload = {
                        is_other: true,
                        hgnc_id: formGene.value.custom_gene?.hgnc_id || null,
                        gene_symbol: formGene.value.custom_gene?.gene_symbol || '',
                        disease_name: formGene.value.custom_disease?.name || '',
                        mondo_id: formGene.value.custom_disease?.mondo_id || '',
                        moi: formGene.value.custom_moi?.abbreviation || '',
                        plan,
                        date_approved: null
                    };
                } else {
                    const basePlan = buildBasePlan(formGene.value);

                    payload = {
                        hgnc_id: formGene.value.hgnc_id,
                        gene_symbol: formGene.value.gene_symbol,
                        disease_name: formGene.value.disease_name,
                        mondo_id: formGene.value.mondo_id,
                        moi: formGene.value.moi,
                        date_approved: formGene.value.date_approved,
                        plan: basePlan,
                        is_other: false
                    };
                }

                if (isEditing.value) {
                    await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/${isEditing.value}`, payload);
                    store.commit('pushSuccess', `Successfully updated for ${payload.gene_symbol}-${payload.mondo_id}-${payload.moi}`);
                } else {
                    await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, { genes: [payload] });
                    store.commit('pushSuccess', `Successfully added: ${payload.gene_symbol}-${payload.mondo_id}-${payload.moi}`);
                }

                await getGenes();
                cancelForm();
                emit('saved');
            } catch (error) {                
                const res = error?.response
                if (res?.status === 422 && res.data?.errors) {
                    const all = Object.values(res.data.errors).flat()
                    store.commit('pushError', all.join('\n'))
                } else {
                    store.commit('pushError', res?.data?.message || `Failed to add ${payload.gene_symbol}`)
                }
            }
        };

        const remove = async gene => {
            try {
                await api.delete(`/api/groups/${group.value.uuid}/expert-panel/genes/${gene.id}`);
                await getGenes();
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
        };

        const updateTier = async gene => {
            savingTierFor.value = gene.id;
            const oldTier = gene.tier;
            try {
                await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/update-tier`, { ids: [gene.id], tier: gene.tier || null });
                store.commit('pushSuccess', `Tier updated for ${gene.gene_symbol}`);
            } catch (error) {
                gene.tier = oldTier;
                store.commit('pushError', `Failed to update tier`);
            } finally {
                savingTierFor.value = null;
            }
        };

        const applyBulkTier = async () => {
            if (!bulkTier.value || selectedGenes.value.length === 0) return;
            try {
                await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/update-tier`, { ids: selectedGenes.value, tier: bulkTier.value });
                store.commit('pushSuccess', `Tier updated for ${selectedGenes.value.length} genes`);
                selectedGenes.value = [];
                await getGenes();                
                bulkTier.value = '';
            } catch (error) {
                store.commit('pushError', 'Failed to update tiers in bulk');
            }
        };

        const clearSelection = () => {
            if (selectedGenes.value.length === 0) return
            selectedGenes.value = [] 
        }

        const toggleSelect = id => {
            if (selectedGenes.value.includes(id)) {
                selectedGenes.value = selectedGenes.value.filter(x => x !== id);
            } else {
                selectedGenes.value.push(id);
            }
        };

        const toggleSelectAll = () => {
            const idsOnPage = paginatedGenes.value.map(g => g.id);
            const allSelected = idsOnPage.every(id => selectedGenes.value.includes(id));
            if (allSelected) {
                selectedGenes.value = selectedGenes.value.filter(id => !idsOnPage.includes(id));
            } else {
                selectedGenes.value = [...new Set([...selectedGenes.value, ...idsOnPage])];
            }
        };

        const filteredAndSortedGenes = computed(() => {
            let result = [...genes.value];
            const keyword = search.value.toLowerCase();
            if (keyword) {
                result = result.filter(g =>
                    (g.gene_symbol && g.gene_symbol.toLowerCase().includes(keyword)) ||
                    (g.mondo_id && g.mondo_id.toLowerCase().includes(keyword)) ||
                    (g.disease_name && g.disease_name.toLowerCase().includes(keyword)) ||
                    (g.moi && g.moi.toLowerCase().includes(keyword)) ||
                    (g.plan?.the_plan && g.plan.the_plan.toLowerCase().includes(keyword)) ||
                    (g.plan?.expert_panel && g.plan.expert_panel.toLowerCase().includes(keyword)) ||
                    (g.plan?.classification && g.plan.classification.toLowerCase().includes(keyword)) ||
                    (g.plan?.phenotypes && g.plan.phenotypes.toLowerCase().includes(keyword))
                );
            }
            if (filterMoi.value) {
                result = result.filter(g => g.moi === filterMoi.value);
            }
            if (filterClassification.value) {
                result = result.filter(g => g.plan?.classification === filterClassification.value);
            }
            // Sorting logic
            result.sort((a, b) => {
                let aVal, bVal;

                switch (sortKey.value) {
                    case 'disease':
                        aVal = `${a.mondo_id || ''} ${a.disease_name || ''}`.toLowerCase();
                        bVal = `${b.mondo_id || ''} ${b.disease_name || ''}`.toLowerCase();
                        break;
                    case 'moi':
                        aVal = a.moi || '';
                        bVal = b.moi || '';
                        break;
                    case 'tier':
                        // Numeric sort for tier
                        aVal = a.tier ? Number(a.tier) : 0;
                        bVal = b.tier ? Number(b.tier) : 0;
                        return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal;
                    default: // gene_symbol
                        aVal = a.gene_symbol || '';
                        bVal = b.gene_symbol || '';
                }

                // For string comparisons
                return sortOrder.value === 'asc'
                    ? aVal.localeCompare(bVal)
                    : bVal.localeCompare(aVal);
            });

            return result;
        });

        const applyGtUpdate = async (gene) => {
            try {
                const snap = gene.gt_data;
                if (!snap) return;

                const payload = {
                    is_other: false,
                    hgnc_id: snap.hgnc_id,
                    gene_symbol: snap.gene_symbol,
                    disease_name: snap.disease_name,
                    mondo_id: snap.mondo_id,
                    moi: snap.moi,
                    date_approved: snap.date_approved,                    
                    plan: {
                        ...snap,
                        checkKey: snap.checkKey,
                        requires_plan: ['Moderate', 'Limited'].includes(snap.plan?.classification),
                    },
                };

                await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/${gene.id}`, payload);
                store.commit('pushSuccess', `Updated ${gene.gene_symbol} from GeneTracker`);
                await getGenes();
            } catch (e) {
                store.commit('pushError', 'Failed to apply update from GeneTracker');
            }
        };


        const moiOptions = computed(() => [...new Set(genes.value.map(g => g.moi).filter(Boolean))]);
        const classificationOptions = computed(() => [...new Set(genes.value.map(g => g.plan?.classification).filter(Boolean))]);
        const totalPages = computed(() => Math.ceil(filteredAndSortedGenes.value.length / pageSize.value));
        const paginatedGenes = computed(() => {
            const start = (currentPage.value - 1) * pageSize.value;
            return filteredAndSortedGenes.value.slice(start, start + pageSize.value);
        });
        const isAllSelected = computed(() => paginatedGenes.value.every(g => selectedGenes.value.includes(g.id)));


        // Helper functions for compare diff rows
        const isDiff = (a, b) => (a ?? '') !== (b ?? '');
        const snapVal = (gene, key) => {
            const s = gene?.plan ?? {};
            const map = {
                gene_symbol:  gene.gene_symbol ?? s.gene_symbol,
                moi:          gene.moi ?? s.moi,
                mondo_id:     gene.mondo_id ?? s.mondo_id,
                disease_name: gene.disease_name ?? s.disease_name,
                expert_panel:s.expert_panel,
                classification: s.classification,
                curation_status: s.curation_status,
                date_approved: s.date_approved,
                phenotypes:   s.phenotypes,
            };
            return map[key];
        };

        const gtVal = (gene, key) => {
            const g = gene?.gt_data ?? {};
            const map = {
                gene_symbol: g.gene_symbol,
                moi:         g.moi,
                mondo_id:    g.mondo_id,
                disease_name:g.disease_name,
                expert_panel:g.expert_panel,
                classification: g.classification,
                curation_status: g.curation_status,
                date_approved: g.date_approved,
                phenotypes:  g.phenotypes,
            };
            return map[key];
        };

        const compactDiffRows = (gene, { onlyChanged = false } = {}) => {
            // Format dates for display consistency
            const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : '');

            const rows = [
                { key: 'gene_symbol',   label: 'HGNC Symbol',   a: snapVal(gene, 'gene_symbol'),   b: gtVal(gene, 'gene_symbol') },
                { key: 'moi',           label: 'MOI',           a: snapVal(gene, 'moi'),           b: gtVal(gene, 'moi') },
                { key: 'mondo_id',      label: 'MONDO ID',      a: snapVal(gene, 'mondo_id'),      b: gtVal(gene, 'mondo_id') },
                { key: 'disease_name',  label: 'Disease',       a: snapVal(gene, 'disease_name'),  b: gtVal(gene, 'disease_name') },

                { key: 'expert_panel',  label: 'Expert Panel',  a: snapVal(gene, 'expert_panel'),  b: gtVal(gene, 'expert_panel') },
                { key: 'classification',label: 'Classification',a: snapVal(gene, 'classification'),b: gtVal(gene, 'classification') },
                { key: 'curation_status',label:'Status',        a: snapVal(gene, 'curation_status'),b: gtVal(gene, 'curation_status') },
                { key: 'date_approved', label: 'Date Approved', a: fmtDate(snapVal(gene, 'date_approved')), b: fmtDate(gtVal(gene, 'date_approved')) },
                { key: 'phenotypes',    label: 'Phenotypes',    a: snapVal(gene, 'phenotypes'),    b: gtVal(gene, 'phenotypes') },
            ];

            return onlyChanged ? rows.filter(r => isDiff(r.a, r.b)) : rows;
        };

        // NEXT 2 FUNCTIONS HANDLE SCROLLING TO FORM WHEN IT OPENS
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



        onMounted(() => { getGenes(); getMois(); });
        const expanded = ref([])
        const toggleExpanded = index => {
            if (expanded.value.includes(index)) {
                expanded.value = expanded.value.filter(i => i !== index)
            } else {
                expanded.value.push(index)
            }
        }

        return {
            group, genes, formGene, curatedGeneKey, isEditing, isFormVisible,
            mois, errors, search, filterMoi, filterClassification, sortKey, sortOrder,
            pageSize, currentPage, totalPages, paginatedGenes, filteredAndSortedGenes,
            moiOptions, classificationOptions,
            selectedGenes, bulkTier, savingTierFor, isAllSelected,
            canEdit, isOtherFormValid, isCuratedPlanValid, toggleExpanded, expanded,
            showConfirmRemove, selectedGene, confirmRemove, cancelRemove, removeGene,
            setSort, startAdd, startEdit, cancelForm, selectOther, handleCuratedSelection, saveForm,
            remove, updateTier, applyBulkTier, toggleSelect, toggleSelectAll, applyGtUpdate,
            compactDiffRows, isDiff, htmlFromMarkdown, formEl, onFormEntered, clearSelection
        };
    }
};
</script>


<style scoped>
/* Fade + slide transition for collapsible form */
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
.btn-xs {
  font-size: 12px;
  padding: 4px 6px;
}

/* Form elements */
input[type="text"], select {
  border: 1px solid #d1d5db;
  border-radius: 4px;
  padding: 4px 8px;
  font-size: 14px;
}
input:focus, select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 1px #2563eb;
}

/* Responsive filter controls */
@media (max-width: 768px) {
  .flex.justify-between {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  .flex.items-center.gap-4 {
    flex-wrap: wrap;
  }
}

.scroll-target { scroll-margin-top: 96px; }
</style>