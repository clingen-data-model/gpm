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
                            v-if="hasAnyPermission([
                                'ep-applications-manage', 
                                ['application-edit', this.group]
                            ])"
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
                                        <p>There are no genes in your gene list.</p>
                                        <button class="btn blue btn-sm" @click="addNewGene">Add a gene/disease pair</button>
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
                                        v-if="hasAnyPermission([
                                            'ep-applications-manage', 
                                            ['application-edit', this.group]
                                        ])"
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
                <transition name="slide-fade-down">
                    <thead v-if="newGenes.length > 0">
                        <tr>
                            <td colspan="5" class="bg-white border-white h-4"></td>
                        </tr>
                        <tr>
                            <td>HGNC Symbol</td>
                            <td colspan="4">Disesase</td>
                        </tr>
                    </thead>
                </transition>

                <tbody>
                    <transition-group name="slide-fade-down">                
                        <tr v-for="(newGene, idx) in newGenes" :key="idx">
                            <td>
                                <input-row label="" :errors="errors[`genes.${idx}.hgnc_id`]" :vertical="true">
                                    <gene-search-select v-model="newGene.gene"></gene-search-select>
                                </input-row>
                            </td>
                            <td colspan="4">
                                <input-row label="" :errors="errors[`diseases.${idx}.hgnc_id`]" :vertical="true">
                                    <disease-search-select v-model="newGene.disease"></disease-search-select>
                                </input-row>
                            </td>
                        </tr>
                    </transition-group>
                </tbody>
                <tr>
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
                </tr>
            </table>
        </div>
    </div>
</template>
<script>
import api from '@/http/api'
import {ref, computed, onMounted} from 'vue';
import {useStore} from 'vuex';
import GeneSearchSelect from '@/components/forms/GeneSearchSelect'
import DiseaseSearchSelect from '@/components/forms/DiseaseSearchSelect'
import is_validation_error from '@/http/is_validation_error'
import {isEqual} from 'lodash'


export default {
    name: 'VcepGeneList',
    components: {
        GeneSearchSelect,
        DiseaseSearchSelect,
    },
    props: {
        group: {
            required: true,
            type: Object
        }
    },
    emits: [
        'saved',
        'canceled',
        'editing'
    ],
    setup(props, context) {
        const store = useStore();
        const newGenes = ref([]);
        const addNewGene = () => {
            newGenes.value.push({gene: null, disease: null});
        }

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

        const clearNewGenes = () => {
            newGenes.value = [];
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
                            await api.delete(`/api/groups/${props.group.uuid}/application/genes/${gene.id}`)
                            await getGenes();
                        } catch (error) {
                            store.commit('pushError', error.response.data);
                        }
                    }
                }
                console.log(genes.value.length)
                clearRemoveCountdown(gene)
            }, 10000);

            gene.removeCountdown = 10;
            gene.removeInterval = setInterval(() => {
                gene.removeCountdown -= 1;
            }, 1000);
            
        }
        
        const getGenes = async () => {
            loading.value = true;
            try {
                genes.value = await api.get(`/api/groups/${props.group.uuid}/application/genes`)
                                .then(response => {
                                    return response.data;
                                });
            } catch (error) {
                this.$store.commit('pushError', error.response.data);
            }
            loading.value = false;
        }

        const save = async () => {
            try {
                if (newGenes.value.length > 0) {
                    const filteredGenes = newGenes.value
                                            .filter(ng => !isEqual(ng, {gene: null, disease: null}))
                                            .map(scope => ({ hgnc_id: scope.gene.hgnc_id, mondo_id: scope.disease.mondo_id}));

                await api.post(`/api/groups/${props.group.uuid}/application/genes`, {genes: filteredGenes});
                    await getGenes();
                }
                clearNewGenes();
                errors.value = {};
                context.emit('saved')
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        };

        const updateGene = async (gene) => {
            try {
                gene.hgnc_id = gene.gene.hgnc_id;
                gene.mondo_id = gene.disease.mondo_id;
                await api.put(`/api/groups/${props.group.uuid}/application/genes/${gene.id}`, gene);
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
            console.log('cancel update for gene')
        }

        const cancel = () => {
            clearNewGenes();
        }

        onMounted(() => {
            getGenes();
        });

        return {
            genes,
            newGenes,
            orderedGenes,
            errors,
            loading,
            addNewGene,
            updateGene,
            updateCancel,
            save,
            cancel,
            edit, 
            remove,
            cancelPendingRemove,
        }        
    }
}
</script>
<style lang="postcss" scope>
    table {
        @apply w-full;
    }
    thead > tr {
         @apply bg-gray-200;
    }
    tbody > tr {
        @apply bg-white odd:bg-gray-100 border-0 hover:border-blue-300 hover:bg-blue-100
    }
    tr > th {
        @apply text-left border border-gray-300 px-3
    }
    tr > td {
        @apply text-left p-1 px-3 border align-top;
    }
    th.sorted, td.sorted  {
        @apply bg-blue-100 hover:bg-blue-100
    }
</style>
