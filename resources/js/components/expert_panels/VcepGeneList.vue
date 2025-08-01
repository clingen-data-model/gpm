<script>
import {api} from '@/http'
import {ref, computed, onMounted} from 'vue';
import {debounce} from 'lodash-es'
import {useStore} from 'vuex';
import GeneSearchSelect from '@/components/forms/GeneSearchSelect.vue'
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect.vue'
import CuratedGeneSearchSelect  from '@/components/forms/CuratedGeneSearchSelect.vue'
import is_validation_error from '@/http/is_validation_error'
import {hasAnyPermission} from '@/auth_utils'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'

export default {
    name: 'VcepGeneList',
    components: {
		GeneSearchSelect,
        DiseaseSearchSelect,
		CuratedGeneSearchSelect,
        RichTextEditor,
    },
    props: {
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'saved',
        'canceled',
        'editing'
    ],
    setup(props, context) {
        const store = useStore();
        const curatedGeneKey = ref(0);
        const showConfirmRemove = ref(false);
        const selectedGene = ref({gene: {}, disease: {}});
        const manualSaveTriggered = ref(false);
        const mois = ref([]);
        const getMois = async () => {
            try {
                const response = await api.get('/api/mois');
                mois.value = response.data; // Assuming response.data is the array
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
        };

        const group = computed(() => {
            return store.getters['groups/currentItemOrNew'];
        });

        const selectedGenes = ref([]) // For bulk tier actions
        const bulkTier = ref('')
        const savingBulk = ref(false)

        const newGene = ref(null);
        const savingTierFor = ref(null)
        const loading = ref(false);
        const errors = ref({});
        const genes = ref([]);        

        const filterMoi = ref(''); // Selected MOI filter
        const filterClassification = ref(''); // Selected classification filter
        const sortKey = ref('gene_symbol'); // Default sort column
        const sortOrder = ref('asc'); // asc or desc
        
        const setSort = (key) => {
            if (sortKey.value === key) {
                sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
            } else {
                sortKey.value = key;
                sortOrder.value = 'asc';
            }
        };

        const clearNewGene = () => {
            newGene.value = null;
            errors.value = {};
            curatedGeneKey.value++; 
        }

        const edit = (gene) => {
            gene.edit = true;
        }

        const clearRemoveCountdown = (gene) => {
            if (gene.removeTimeout) {
                clearTimeout(gene.removeTimeout);
                clearInterval(gene.removeInterval);
                gene.removeCountdown = 10;
            }
        }

        const clearRemovalFlags = (gene) => {
            delete(gene.toDelete);
            delete(gene.removeInterval);
            delete(gene.removeTimeout);
        }

        const cancelPendingRemove = (gene) => {
            clearRemoveCountdown(gene);
            clearRemovalFlags(gene);
        }

        const confirmRemove = (gene) => {
            selectedGene.value = gene;
            showConfirmRemove.value = true;
        }

        const cancelRemove = () => {
            selectedGene.value = {gene: {}, disease: {}};
            showConfirmRemove.value = false;
        }

        const isOtherFormValid = computed(() => {
            console.log('Validating Other Form', newGene.value);
            if (!newGene.value || !newGene.value.is_other) return false;
            
            const geneValid = !!newGene.value.custom_gene?.hgnc_id;
            const diseaseValid = !!newGene.value.custom_disease?.mondo_id;
            const planValid = newGene.value.custom_plan?.trim().length >= 20;

            return geneValid && diseaseValid && planValid;
        });

        const triggerManualSave = () => {
            manualSaveTriggered.value = true;
            save();
            manualSaveTriggered.value = false; // Reset after save
        };



        const getGenes = async () => {
            if (!group.value.uuid)  {
                return;
            }
            loading.value = true;
            try {
                genes.value = await api.get(`/api/groups/${group.value.uuid}/expert-panel/genes?with[]=gene&with[]=disease`)
                    .then(response => response.data);

                // need to set genes on the expert_panel for requirements validation
                group.value.expert_panel.genes = genes.value;
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
            loading.value = false;
        }

        const remove = async (gene) => {
            try {
                await api.delete(`/api/groups/${group.value.uuid}/expert-panel/genes/${gene.id}`)
                await getGenes();
                cancelRemove();
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
        }

        const save = async () => {
            try {
				if(! newGene.value) return;
                if (newGene.value.is_other && !manualSaveTriggered.value) return;

				let payload;

				if(newGene.value.is_other) {
                    const myPlan = {
                        is_other: true,
                        the_plan: newGene.value.custom_plan,
                        moi_name: newGene.value.custom_moi?.name || '',
						hp_id: newGene.value.custom_moi?.hp_id || '',
                    }
					payload = {
						is_other: true,
                        hgnc_id: newGene.value.custom_gene?.hgnc_id || null,
                        gene_symbol: newGene.value.custom_gene?.gene_symbol || '',
                        disease_name: newGene.value.custom_disease?.name || '',
                        mondo_id: newGene.value.custom_disease?.mondo_id || '',                        
                        moi: newGene.value.custom_moi?.abbreviation || '',
                        plan: myPlan,
                        date_approved: null,
					};
				} else {
					payload = {
						hgnc_id: newGene.value.hgnc_id,
                        gene_symbol: newGene.value.gene_symbol,
                        disease_name: newGene.value.disease_name,
						mondo_id: newGene.value.mondo_id,
						moi: newGene.value.moi,
                        date_approved: newGene.value.date_approved,
                        plan: newGene.value,
                        is_other: false,
					}
				}

				await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, {
					genes: [ payload ]
				});

				await getGenes();
				clearNewGene();
                errors.value = {};
                context.emit('saved')
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        };

        const debounceSave = debounce(save, 500)

        const updateGene = async (gene) => {
            try {
                gene.hgnc_id = gene.gene.hgnc_id;
                gene.mondo_id = gene.disease.mondo_id;
                await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/${gene.id}`, gene);
                await getGenes();
                delete(gene.edit);
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        }
        const updateCancel = gene => {
            delete(gene.edit);
        }

        const cancel = () => {
            clearNewGene();
        }

        // Update single tier
        const updateTier = async gene => {
            savingTierFor.value = gene.id
            const oldTier = gene.tier
            try {
                await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/update-tier`, {
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
                await api.put(`/api/groups/${group.value.uuid}/expert-panel/genes/update-tier`, {
                    ids: selectedGenes.value,
                    tier: bulkTier.value
                })
                store.commit('pushSuccess', `Tier updated for ${selectedGenes.value.length} genes`)
                
                genes.value.forEach(g => {
                    if (selectedGenes.value.includes(g.id)) {
                        g.tier = bulkTier.value
                    }
                })
                selectedGenes.value = []
                bulkTier.value = ''
            } catch (error) {
                console.error('Bulk tier update error:', error.response?.data || error.message);
                store.commit('pushError', 'Failed to update tiers in bulk')
            } finally {
                savingBulk.value = false
            }
        }

        const canEdit = computed(() => {
            return hasAnyPermission(['ep-applications-manage', ['application-edit', group.value]])
                && !props.readonly
        })

        // Selection
        const toggleSelect = geneId => {
            if (selectedGenes.value.includes(geneId)) {
                selectedGenes.value = selectedGenes.value.filter(id => id !== geneId)
            } else {
                selectedGenes.value.push(geneId)
            }
        }

        const toggleSelectAll = () => {
            const idsOnPage = filteredAndSortedGenes.value.map(g => g.id)
            const allSelected = idsOnPage.every(id => selectedGenes.value.includes(id))
            if (allSelected) {
                selectedGenes.value = selectedGenes.value.filter(id => !idsOnPage.includes(id))
            } else {
                selectedGenes.value = [...new Set([...selectedGenes.value, ...idsOnPage])]
            }
        }
        const isAllSelected = computed(() =>
            filteredAndSortedGenes.value.every(g => selectedGenes.value.includes(g.id))
        )

        const selectOther = () => {
            newGene.value = {
                is_other: true,
                custom_gene: null,
                custom_disease: null,
                custom_moi: null,
                custom_plan: ''
            };
        };

        const handleCuratedSelection = (selected) => {
            if (selected && !selected.is_other) {
                newGene.value = selected; // Normal curated selection
            }
        };

        const filteredAndSortedGenes = computed(() => {
            let result = [...genes.value];

            // Apply MOI filter
            if (filterMoi.value) {
                result = result.filter(g => g.moi === filterMoi.value);
            }

            // Apply Classification filter
            if (filterClassification.value) {
                result = result.filter(g => g.plan?.classification === filterClassification.value);
            }

            // Sorting logic
            result.sort((a, b) => {
                let aVal, bVal;

                switch (sortKey.value) {
                    case 'disease':
                        aVal = `${a.mondo_id} ${a.disease_name}`.toLowerCase();
                        bVal = `${b.mondo_id} ${b.disease_name}`.toLowerCase();
                        break;
                    case 'moi':
                        aVal = a.moi || '';
                        bVal = b.moi || '';
                        break;
                    case 'tier':
                        aVal = a.tier || 0;
                        bVal = b.tier || 0;
                        break;
                    default: // gene_symbol
                        aVal = a.gene_symbol || '';
                        bVal = b.gene_symbol || '';
                }

                if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
                if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
                return 0;
            });

            return result;
        });

        const moiOptions = computed(() => {
            return [...new Set(genes.value.map(g => g.moi).filter(Boolean))];
        });

        const classificationOptions = computed(() => {
            return [...new Set(genes.value.map(g => g.plan?.classification).filter(Boolean))];
        });



        onMounted(() => {
            getGenes();
            getMois();
        });

        return {
            group,
            genes,
            newGene,
            filteredAndSortedGenes,
            errors,
            loading,
            updateGene,
            updateCancel,
            save,
            debounceSave,
            cancel,
            edit,
            remove,
            cancelPendingRemove,
            canEdit,
            showConfirmRemove,
            confirmRemove,
            cancelRemove,
            selectedGene,
            isOtherFormValid,
            triggerManualSave,
            mois,
            savingTierFor,
            updateTier,
            selectedGenes,
            toggleSelect,
            toggleSelectAll,
            isAllSelected,
            bulkTier,
            applyBulkTier,
            selectOther,
            handleCuratedSelection,
            curatedGeneKey,
            sortKey,
            sortOrder,
            setSort,
            moiOptions,
            filterMoi,
            filterClassification,
            classificationOptions,
        }
    },
    computed: {
        selectedGeneSymbol () {
            return this.selectedGene && this.selectedGene.gene ?  this.selectedGene.gene.gene_symbol : 'Unknown Gene Symbols'
        },
        selectedDiseaseName () {
            return this.selectedGene && this.selectedGene.disease ?  this.selectedGene.disease.name : 'Unknown Disease Name'
        }
    }
}
</script>
<template>
	<div>
		<header class="flex justify-between items-center">
			<h4>Gene/Disease List</h4>
		</header>
		<div class="my-2">

            <div class="flex justify-between items-center bg-gray-50 p-3 border rounded mb-3">                
                <!-- Left: Bulk Tier Update -->
                <div class="flex items-center gap-2">
                    <template v-if="selectedGenes.length > 0 && !readonly" >
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
                    </template>
                </div>

                <!-- Right: Filters -->
                <div class="flex gap-3">
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

			<table v-if="genes" class="border-none">
				<thead>
					<tr>
                        <th v-if="canEdit"><input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" /></th>
						<th @click="setSort('gene_symbol')" class="cursor-pointer">
                            HGNC Symbol
                            <span v-if="sortKey === 'gene_symbol'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </th>
                        <th @click="setSort('disease')" class="cursor-pointer">
                            Disease
                            <span v-if="sortKey === 'disease'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </th>
                        <th @click="setSort('moi')" class="cursor-pointer">
                            MOI
                            <span v-if="sortKey === 'moi'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </th>                        
                        <th>Note</th>
                        <th @click="setSort('tier')" class="cursor-pointer">
                            Tier
                            <span v-if="sortKey === 'tier'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </th>
					</tr>
				</thead>
				<transition name="fade" mode="out-in">
				<tbody v-if="genes.length == 0">
					<tr>
						<td colspan="6">
							<div class="p-2 text-center font-bold">
							<span v-if="loading">Loading...</span>
							<div v-else>
								<p>There are no gene/disease pairs in the gene list.</p>
							</div>
							</div>
						</td>
					</tr>
				</tbody>
				<tbody v-else>
					<!-- <transition-group name="slide-fade-down"> -->
					<tr v-for="gene in filteredAndSortedGenes" :key="gene.id">
					<template v-if="!gene.edit">
                        <td v-if="canEdit">                            
                            <input
                                type="checkbox"
                                :checked="selectedGenes.includes(gene.id)"
                                @change="toggleSelect(gene.id)"
                                :disabled="readonly"
                            />
                        </td>
						<td>{{ gene.gene_symbol }}</td>
						<td>{{ gene.mondo_id }} <template v-if="gene.mondo_id"><br /></template> {{ gene.disease_name }}</td>
                        <td>{{ gene.moi }}</td>
						<td>
                            <div v-if="! gene.plan.is_other" class="grid grid-cols-2 gap-x-4 text-xs text-gray-600 leading-snug">                                
                                <div><strong>Expert Panel:</strong> {{ gene.plan.expert_panel ?? 'N/A' }}</div>
                                <div><strong>Classification:</strong> {{ gene.plan.classification ?? 'N/A' }}</div>
                                <div><strong>Status:</strong> {{ gene.plan.curation_status ?? 'N/A' }}</div>
                                <div><strong>Date Approved:</strong> 
                                    {{ gene.plan.date_approved ? new Date(gene.plan.date_approved).toLocaleDateString('en-US', {
                                        month: 'short', // Dec
                                        day: '2-digit', // 14
                                        year: 'numeric', // 2025
                                        hour: '2-digit', // 02
                                        minute: '2-digit', // 30
                                        hour12: true     // AM/PM
                                    }) : 'N/A' }}
                                </div>
                                <div class="col-span-2"><strong>Phenotypes:</strong> {{ gene.plan.phenotypes ?? 'N/A' }}</div>
                            </div>
                            <div v-else v-html="gene.plan.the_plan"></div>
                        </td>
						<td v-if="canEdit">
                            <div class="flex flex-col items-start gap-1">
                                <!-- Tier dropdown -->
                                <select
                                    v-model="gene.tier"
                                    class="border rounded px-1 py-0.5 text-sm"
                                    @change="updateTier(gene)"
                                    :disabled="savingTierFor === gene.id || readonly"
                                    >
                                    <option value="">—</option>
                                    <option value="1">Tier 1</option>
                                    <option value="2">Tier 2</option>
                                    <option value="3">Tier 3</option>
                                    <option value="4">Tier 4</option>
                                </select>
                                <span v-if="savingTierFor === gene.id" class="text-xs text-gray-500">Saving...</span>
                                
                                <!-- Dropdown menu -->
                                <dropdown-menu v-if="!gene.toDelete" :hide-cheveron="true" class="relative">
                                <template #label>
                                    <button class="btn btn-xs w-full text-center">⋯</button>
                                </template>
                                <dropdown-item @click="edit(gene)">Edit</dropdown-item>
                                <dropdown-item @click="confirmRemove(gene)">Remove</dropdown-item>
                                </dropdown-menu>                                
                            </div>
                        </td>
					</template>
					<template v-else>
						<td colspan="6">
							<CuratedGeneSearchSelect v-model="newGene" />
						</td>
						<td>
							<button class="btn btn-xs" @click="updateCancel(gene)">
								Cancel
							</button>
							<button class="btn blue btn-xs" @click="updateGene(gene)">
								Save
							</button>
						</td>
					</template>
					</tr>
					<!-- </transition-group> -->
				</tbody>
				</transition>

				<tbody v-if="canEdit">
					<tr>
						<td colspan="6">
                            <div class="space-y-2">
                                <!-- Label -->
                                <label class="block font-semibold">Select Gene-Disease-MOI:</label>

                                <!-- CuratedGeneSearchSelect -->
                                <CuratedGeneSearchSelect
                                    v-model="newGene"
                                    @update:model-value="handleCuratedSelection"
                                    placeholder="Search curated Gene-Disease-MOI"
                                    class="w-full"
                                    :key="curatedGeneKey"
                                />

                                <!-- Other Option Button -->
                                <button
                                    type="button"
                                    class="text-blue-600 underline text-sm hover:text-blue-800"
                                    @click="selectOther"
                                >
                                    Click here for <strong>Other (Not Curated)</strong>
                                </button>
                            </div>
							<!-- Show free-text inputs if "Other" is selected -->
							<div v-if="newGene && newGene.is_other" class="border rounded bg-gray-50 p-3 mb-3">
                                <h5 class="text-lg font-semibold mb-2">Provide details for non-curated gene</h5>

                                <div class="grid grid-cols-2 gap-2">
                                    <!-- Gene Select -->
                                     <div>
                                        <label class="block text-sm font-medium mb-1">Gene *</label>                                        
                                        <GeneSearchSelect v-model="newGene.custom_gene" placeholder="Search gene..." />
                                        <p v-if="errors['custom_gene']" class="text-red-500 text-xs mt-1">{{ errors['custom_gene'][0] }}</p>
                                    </div>
                                    <!-- MOI -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">MOI</label>    
                                        <select 
                                            v-model="newGene.custom_moi" 
                                            class="form-select w-full"
                                        >
                                            <option value="">----Select MOI-----</option>
                                            <option 
                                                v-for="moi in mois" 
                                                :key="moi.id" 
                                                :value="moi"
                                            >
                                                {{ moi.name }} ({{ moi.abbreviation }})
                                            </option>
                                        </select>
                                        <p v-if="errors['custom_moi']" class="text-red-500 text-xs mt-1">{{ errors['custom_moi'][0] }}</p>
                                    </div>                                    
                                    <!-- Disease Select -->
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium mb-1">Disease *</label>    
                                        <DiseaseSearchSelect v-model="newGene.custom_disease" placeholder="Search disease..." />
                                    </div>
                                    <!-- Plan -->                                    
                                    <div class="col-span-2 mb-3">
                                        <label class="block text-sm font-medium mb-1">Plan for Curation *</label>
                                        <RichTextEditor 
                                            v-model="newGene.custom_plan" 
                                            placeholder="Describe your plan (min 20 characters)"
                                            class="w-full"
                                        />
                                        <p v-if="errors['custom_plan']" class="text-red-500 text-xs mt-1">
                                            {{ errors['custom_plan'][0] }}
                                        </p>
                                    </div>
                                    <div class="text-right mt-4 col-span-2 gap-2">
                                        <button type="button" class="btn btn-gray mr-2" @click="cancel">
                                            Cancel
                                        </button>
                                        &nbsp;
                                        <button class="btn blue"
                                                :disabled="!isOtherFormValid"
                                                @click="triggerManualSave">
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<modal-dialog v-model="showConfirmRemove" title="Confirm gene/disease pair delete.">
			<p>You are about to delete the gene/disease pair {{ selectedGene.gene_symbol }}/{{ selectedGene.disease_name }}.  Are you sure you want to continue?</p>
			<button-row
				submit-text="Yes, delete it."
				cancel-text="No, cancel"
				@submitted="remove(selectedGene)"
				@canceled="cancelRemove"
			/>
		</modal-dialog>
	</div>
</template>
