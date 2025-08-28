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

            <!-- Page size -->
            <div class="flex items-center gap-2">
                <label class="text-sm">Page size:</label>
                <select v-model="pageSize" class="border px-2 py-1 rounded text-sm">
                    <option v-for="size in [20, 50, 100]" :key="size" :value="size">{{ size }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <transition name="fade">
        <div v-if="isFormVisible" class="border rounded bg-gray-50 p-4 mb-4">
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
                        This gene’s classification is <strong>{{ formGene.plan?.classification }}</strong>.  
                        Please describe your plan for curation.
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
                        <label class="block text-sm font-medium mb-1">Disease *</label>
                        <DiseaseSearchSelect v-model="formGene.custom_disease" placeholder="Search disease..." />
                    </div>

                    <!-- Plan -->
                    <div class="col-span-2 mb-3">
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
    <table v-if="genes" class="border-none w-full">
        <thead>
            <tr>
                <th v-if="canEdit && editing"><input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" /></th>
                <th @click="setSort('gene_symbol')" class="cursor-pointer">
                    HGNC Symbol <span v-if="sortKey === 'gene_symbol'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th @click="setSort('disease')" class="cursor-pointer">
                    Disease <span v-if="sortKey === 'disease'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th @click="setSort('moi')" class="cursor-pointer">
                    MOI <span v-if="sortKey === 'moi'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
                <th>Plan</th>
                <th @click="setSort('tier')" class="cursor-pointer">
                    Tier <span v-if="sortKey === 'tier'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                </th>
            </tr>
        </thead>
        <tbody v-if="paginatedGenes.length === 0">
            <tr>
                <td colspan="6" class="p-3 text-center">No genes found.</td>
            </tr>
        </tbody>
        <tbody>
            <template v-for="(gene, index) in paginatedGenes" :key="gene.id">
            <tr :style="gene.plan?.classification === 'Moderate' ? 'background-color: oklch(98.7% 0.026 102.212);' : gene.plan?.classification === 'Limited' || gene.is_outdated ? 'background-color: oklch(97.3% 0.071 103.193);' : ''">
                <td v-if="canEdit & editing">
                    <input type="checkbox" :checked="selectedGenes.includes(gene.id)" @change="toggleSelect(gene.id)" />
                </td>
                <td>{{ gene.gene_symbol }}</td>
                <td>{{ gene.mondo_id }}<br />{{ gene.disease_name }}</td>
                <td>{{ gene.moi }}</td>
                <td class="text-xs">
                    <div v-if="!gene.plan?.is_other" class="grid grid-cols-2 gap-x-4 text-gray-600">
                        <div><strong>Expert Panel:</strong> {{ gene.plan?.expert_panel ?? 'N/A' }}</div>
                        <div>
                            <strong>Classification:</strong> {{ gene.plan?.classification ?? 'N/A' }}
                            <span v-if="['Moderate','Limited'].includes(gene.plan?.classification)" class="font-bold text-orange-500">⚠</span>
                        </div>
                        <div><strong>Status:</strong> {{ gene.plan?.curation_status ?? 'N/A' }}</div>
                        <div><strong>Date Approved:</strong>
                            {{ gene.plan?.date_approved ? new Date(gene.plan?.date_approved).toLocaleDateString() : 'N/A' }}
                        </div>
                        <div class="col-span-2"><strong>Phenotypes:</strong> {{ gene.plan?.phenotypes ?? 'N/A' }}</div>
                        <div class="col-span-2" v-if=" gene.plan?.requires_plan"><strong>Plan:</strong> <span v-html="gene.plan?.curated_plan_text"></span></div>                        
                    </div>
                    <div v-else v-html="gene.plan?.the_plan"></div>                    
                </td>
                <td>
                    <div v-if="editing" class="flex flex-col gap-1">
                        <select
                            v-model="gene.tier"
                            class="border rounded px-1 py-0.5 text-sm"
                            @change="updateTier(gene)"
                            :disabled="savingTierFor === gene.id || readonly"
                        >
                            <option value="null">—</option>
                            <option value="1">Tier 1</option>
                            <option value="2">Tier 2</option>
                            <option value="3">Tier 3</option>
                            <option value="4">Tier 4</option>
                        </select>
                        <button v-if="gene.is_outdated && gene.gt_data" 
                            @click="applyGtUpdate(gene)"
                            class="btn btn-xs w-full text-center text-amber-800 font-semibold"
                            title="Snapshot differs from latest GeneTracker data"

                            >
                            Refresh data from GT ⚠
                        </button>
                        <dropdown-menu v-if="!gene.toDelete" :hide-cheveron="true" class="relative">
                            <template #label>
                                <button class="btn btn-xs w-full text-center">⋯</button>
                            </template>
                            <dropdown-item @click="startEdit(gene)">Edit</dropdown-item>
                            <dropdown-item @click="confirmRemove(gene)">Remove</dropdown-item>
                        </dropdown-menu>                        
                    </div>
                    <span v-else>{{ gene.tier || '—' }}</span>
                </td>
            </tr>
            <tr v-if="gene.is_outdated && gene.gt_data"> <!-- && ! expanded.includes(index) -->
                <td :colspan="editing ? 6 : 5">
                    <div
                        role="status" aria-live="polite"
                        class="rounded-md border border-amber-300 bg-amber-50
                                px-3 py-2 text-xs text-amber-900 col-span-2" 
                        >
                        <div class="flex items-start gap-2">
                            ⚠
                            <div>
                                <div class="font-medium">Snapshot out of date</div>
                                <div class="text-amber-900/80">This record differs from the latest GeneTracker data. 
                                   <span @click="gene.is_outdated && toggleExpanded(index)" class="underline cursor-pointer font-semibold">
                                     {{ expanded.includes(index) ? 'Hide' : 'View' }} GeneTracker data
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr v-if="gene.is_outdated && gene.gt_data && expanded.includes(index)">                
                <td v-if="editing" @click="toggleExpanded(index)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" aria-labelledby="box" role="presentation" name="cheveron-down" class="m-auto cursor-pointer"><title id="box" lang="en">box</title><g fill="currentColor"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"></path></g></svg>
                </td>
                <td>{{ gene.gt_data?.gene_symbol }}</td>
                <td>{{ gene.gt_data?.mondo_id }}<br />{{ gene.gt_data?.disease_name }}</td>
                <td>{{ gene.gt_data?.moi }}</td>
                <td class="text-xs" :colspan="editing ? 2 : 1">
                    <div class="grid grid-cols-2 gap-x-4 text-gray-600">
                        <div><strong>Expert Panel:</strong> {{ gene.gt_data?.expert_panel ?? 'N/A' }}</div>
                        <div>
                            <strong>Classification:</strong> {{ gene.gt_data?.classification ?? 'N/A' }}
                            <span v-if="['Moderate','Limited'].includes(gene.gt_data?.classification)" class="font-bold text-orange-500">⚠</span>
                        </div>
                        <div><strong>Status:</strong> {{ gene.gt_data?.curation_status ?? 'N/A' }}</div>
                        <div><strong>Date Approved:</strong>
                            {{ gene.gt_data?.date_approved ? new Date(gene.gt_data?.date_approved).toLocaleDateString() : 'N/A' }}
                        </div>
                        <div class="col-span-2"><strong>Phenotypes:</strong> {{ gene.gt_data?.phenotypes ?? 'N/A' }}</div>
                    </div>
                </td>
                <td v-if="! editing" @click="toggleExpanded(index)">
                    Hide
                </td>
            </tr>
            </template>
        </tbody>
    </table>

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
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import GeneSearchSelect from '@/components/forms/GeneSearchSelect.vue';
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect.vue';
import CuratedGeneSearchSelect from '@/components/forms/CuratedGeneSearchSelect.vue';
import is_validation_error from '@/http/is_validation_error';
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

        const startEdit = (gene) => {
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
                    custom_plan: gene.plan.the_plan || ''
                };
            } else {
                const plan = {
                    ...gene.plan,
                    is_other: false,
                    curated_plan_text: gene.plan?.curated_plan_text ?? gene.plan?.the_plan ?? ''
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
                    curated_plan_text: plan.curated_plan_text,
                    is_other: false
                };
            }

            curatedGeneKey.value++;
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
                !!formGene.value.custom_disease?.mondo_id &&
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
                    curated_plan_text: ''
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
                curated_plan_text:
                (fg?.requires_plan ? (fg?.curated_plan_text || fromPlan.curated_plan_text || '') 
                                    : (fromPlan.curated_plan_text || ''))
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
                        the_plan: formGene.value.custom_plan,
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
                } else {
                    await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, { genes: [payload] });
                }

                await getGenes();
                cancelForm();
                emit('saved');
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors;
                } else {
                    store.commit('pushError', error.response.data);
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
            remove, updateTier, applyBulkTier, toggleSelect, toggleSelectAll, applyGtUpdate
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

/* Table styles */
table {
  border-collapse: collapse;
  width: 100%;
}
th, td {
  padding: 8px 10px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}
th {
  background-color: #f9fafb;
  font-weight: 600;
  cursor: pointer;
}
th span {
  font-size: 12px;
  margin-left: 4px;
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
</style>