<script>
import {hasAnyPermission} from '@/auth_utils'
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect.vue'
import GeneSearchSelect from '@/components/forms/GeneSearchSelect.vue'
import {api} from '@/http'
import is_validation_error from '@/http/is_validation_error'
import {debounce} from 'lodash-es'
import {computed, onMounted, ref} from 'vue';
import {useStore} from 'vuex';

export default {
    name: 'VcepGeneList',
    components: {
        GeneSearchSelect,
        DiseaseSearchSelect,
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

        const showConfirmRemove = ref(false);
        const selectedGene = ref({gene: {}, disease: {}});

        const group = computed(() => {
            return store.getters['groups/currentItemOrNew'];
        });


        const newGene = ref({gene: null, disease: null});

        const loading = ref(false);
        const errors = ref({});
        const genes = ref([]);
        const orderedGenes = computed(() => {
            const sortedGenes = [...genes.value];
            sortedGenes.sort((a,b) => {
                if (a.gene_symbol === b.gene_symbol) {
                    return 0;
                }
                return (a.gene_symbol > b.gene_symbol) ? 1 : -1;
            });
            return sortedGenes;
        })

        const clearNewGene = () => {
            newGene.value = {gene: null, disease: null};
            errors.value = {};
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
                if (newGene.value.gene !== null && newGene.value.disease !== null) {

                    await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, {
                        genes: [{
                            hgnc_id: newGene.value.gene.hgnc_id,
                            mondo_id: newGene.value.disease.mondo_id
                        }]
                    });
                    await getGenes();

                    clearNewGene();
                }

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

        const canEdit = computed(() => {
            return hasAnyPermission(['ep-applications-manage', ['application-edit', group.value]])
                && !props.readonly
        })

        onMounted(() => {
            getGenes();
        });

        return {
            group,
            genes,
            newGene,
            orderedGenes,
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
            <table v-if="genes" class="border-none">
                <thead>
                    <tr>
                        <th style="min-width: 10rem">HGNC Symbol</th>
                        <th style="min-width: 15rem">Disease</th>
                        <!-- <th style="max-width: 9rem">Date Approved</th> -->
                        <th
                            v-if="canEdit"
                            style="width: 5rem"
                        ></th>
                    </tr>
                </thead>
                <transition name="fade" mode="out-in">
                    <tbody v-if="genes.length === 0">
                        <tr>
                            <td colspan="4">
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
                            <tr v-for="gene in orderedGenes" :key="gene.id">
                                <template v-if="!gene.edit">
                                    <td>{{ gene.gene_symbol }}</td>
                                    <td>{{ gene.disease_name }}</td>
                                    <!-- <td>{{formatDate(gene.date_approved)}}</td> -->
                                    <td
                                        v-if="canEdit"
                                    >
                                        <div v-if="!gene.edit">
                                            <dropdown-menu
                                                v-if="!gene.toDelete"
                                                :hide-cheveron="true"
                                                class="relative"
                                            >
                                                <template #label>
                                                    <button class="btn btn-xs">&hellip;</button>
                                                </template>
                                                <dropdown-item @click="edit(gene)">Edit</dropdown-item>
                                                <dropdown-item @click="confirmRemove(gene)">Remove</dropdown-item>
                                            </dropdown-menu>

                                            <!-- <div v-if="gene.toDelete">
                                                <note>set for deletion in {{gene.removeCountdown}} seconds.</note>
                                                <button @click="cancelPendingRemove(gene)" class="btn btn-xs">Cancel</button>
                                            </div> -->
                                        </div>
                                    </td>
                                </template>
                                <template v-else>
                                    <td>
                                        <input-row label="" :errors="errors.hgnc_id" :vertical="true">
                                            <GeneSearchSelect v-model="gene.gene"></GeneSearchSelect>
                                        </input-row>
                                    </td>
                                    <!-- <td colspan="2"> -->
                                    <td>
                                        <input-row label="" :errors="errors.mondo_id" :vertical="true">
                                            <DiseaseSearchSelect v-model="gene.disease"></DiseaseSearchSelect>
                                        </input-row>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs" @click="updateCancel(gene)">Cancel</button>
                                        <button class="btn blue btn-xs" @click="updateGene(gene)">Save</button>
                                    </td>
                                </template>
                            </tr>
                        <!-- </transition-group> -->
                    </tbody>
                </transition>

                <tbody v-if="canEdit">
                        <tr>
                            <td>
                                <input-row label="" :errors="errors[`genes.0.hgnc_id`]" :vertical="true">
                                    <GeneSearchSelect v-model="newGene.gene" @update:modelValue="debounceSave" />
                                </input-row>
                            </td>
                            <td colspan="4">
                                <input-row label="" :errors="errors[`diseases.0.hgnc_id`]" :vertical="true">
                                    <DiseaseSearchSelect v-model="newGene.disease" @update:modelValue="debounceSave" />
                                </input-row>
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
            ></button-row>
        </modal-dialog>
    </div>
</template>
