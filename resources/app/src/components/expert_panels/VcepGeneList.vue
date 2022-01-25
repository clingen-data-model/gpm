<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Gene/Disease List</h4>
        </header>
        <div class="my-2">
            <table class="border-none" v-if="genes">
                <thead>
                    <tr>
                        <th style="min-width: 10rem">HGNC Symbol</th>
                        <th style="min-width: 15rem">Disease</th>
                        <th style="max-width: 9rem">Date Approved</th>
                        <th
                            v-if="canEdit"
                            style="width: 5rem"
                        ></th>
                    </tr>
                </thead>
                <transition-group name="fade" mode="out-in">                
                    <tbody v-if="genes.length == 0">
                        <tr>
                            <td colspan="4">
                                <div class="p-2 text-center font-bold">
                                    <span v-if="loading">Loading...</span>
                                    <div v-else>
                                        <p>There are no gene/disease pairs in the gene list.</p>
                                        <button class="btn blue btn-sm" @click="addNewGene" v-if="canEdit">Add a gene/disease pair</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <transition-group name="slide-fade-down">
                            <tr v-for="gene in orderedGenes" :key="gene.id">                            
                                <template v-if="!gene.edit">
                                    <td>{{gene.gene_symbol}}</td>
                                    <td>{{gene.disease_name}}</td>
                                    <td>{{formatDate(gene.date_approved)}}</td>
                                    <td
                                        v-if="canEdit"
                                    >
                                        <div v-if="!gene.edit">
                                            <dropdown-menu 
                                                :hide-cheveron="true" 
                                                class="relative"
                                                v-if="!gene.toDelete"
                                            >
                                                <template v-slot:label>
                                                    <button class="btn btn-xs">&hellip;</button>
                                                </template>
                                                <dropdown-item @click="edit(gene)">Edit</dropdown-item>
                                                <dropdown-item @click="remove(gene)">Remove</dropdown-item>
                                            </dropdown-menu>

                                            <div v-if="gene.toDelete">
                                                <note>set for deletion in {{gene.removeCountdown}} seconds.</note>
                                                <button @click="cancelPendingRemove(gene)" class="btn btn-xs">Cancel</button>
                                            </div>
                                        </div>
                                    </td>
                                </template>
                                <template v-else>
                                    <td>
                                        <input-row label="" :errors="errors.hgnc_id" :vertical="true">
                                            <gene-search-select v-model="gene.gene"></gene-search-select>
                                        </input-row>
                                    </td>
                                    <td colspan="2">
                                        <input-row label="" :errors="errors.mondo_id" :vertical="true">
                                            <disease-search-select v-model="gene.disease"></disease-search-select>
                                        </input-row>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs" @click="updateCancel(gene)">Cancel</button>
                                        <button class="btn blue btn-xs" @click="updateGene(gene)">Save</button>
                                    </td>
                                </template>
                            </tr>
                        </transition-group>
                    </tbody>
                </transition-group>
                <!-- <transition name="slide-fade-down">
                    <thead v-if="newGenes.length > 0">
                        <tr>
                            <td colspan="5" class="bg-white border-white h-4"></td>
                        </tr>
                        <tr>
                            <td>HGNC Symbol</td>
                            <td colspan="4">Disesase</td>
                        </tr>
                    </thead>
                </transition> -->

                <tbody v-if="canEdit">
                    <!-- <transition-group name="slide-fade-down">                 -->
                        <tr>
                            <td>
                                <input-row label="" :errors="errors[`genes.0.hgnc_id`]" :vertical="true">
                                    <gene-search-select v-model="newGene.gene" @update:modelValue="debounceSave" />
                                </input-row>
                            </td>
                            <td colspan="4">
                                <input-row label="" :errors="errors[`diseases.0.hgnc_id`]" :vertical="true">
                                    <disease-search-select v-model="newGene.disease" @update:modelValue="debounceSave" />
                                </input-row>
                            </td>
                        </tr>
                    <!-- </transition-group> -->
                </tbody>
                <!-- <tr v-if="canEdit">
                    <td colspan="5" class="border-white">
                        <div class="-mx-2 my-2 flex space-x-2">
                            <button @click="addNewGene" class="btn btn-xs">Add Gene/Disease Pair</button>
                            <transition name="fade">
                                <div class="flex space-x-2">
                                    <button class="btn btn-xs" @click="cancel"  v-if="newGenes.length > 0">Cancel</button>
                                    <button class="btn btn-xs blue" @click="save"  v-if="newGenes.length > 0">Save</button>
                                </div>
                            </transition>
                        </div>
                    </td>
                </tr> -->
            </table>
        </div>
    </div>
</template>
<script>
import api from '@/http/api'
import {ref, computed, onMounted} from 'vue';
import {debounce} from 'lodash'
import {useStore} from 'vuex';
import GeneSearchSelect from '@/components/forms/GeneSearchSelect'
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect'
import is_validation_error from '@/http/is_validation_error'
import {isEqual} from 'lodash'
import {hasAnyPermission} from '@/auth_utils'


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
                if (a.gene_symbol == b.gene_symbol) {
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

        const remove = (gene) => {
            clearRemoveCountdown(gene);
            gene.toDelete = true;
            gene.removeTimeout = setTimeout(async () => {
                for (let idx = 0; idx < genes.value.length; idx++) {
                    if (genes.value[idx].toDelete) {
                        try {
                            await api.delete(`/api/groups/${group.value.uuid}/expert-panel/genes/${gene.id}`)
                            await getGenes();
                        } catch (error) {
                            store.commit('pushError', error.response.data);
                        }
                    }
                }
                clearRemoveCountdown(gene)
            }, 10000);

            gene.removeCountdown = 10;
            gene.removeInterval = setInterval(() => {
                gene.removeCountdown -= 1;
            }, 1000);
            
        }
        
        const getGenes = async () => {
            if (!group.value.uuid)  {
                return;
            }
            loading.value = true;
            try {
                genes.value = await store.dispatch('groups/getGenes', group.value);
            } catch (error) {
                console.log(error);
                store.commit('pushError', error.response.data);
            }
            loading.value = false;
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

        const debounceSave = debounce(save, 2000)

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
            canEdit
        }        
    }
}
</script>
