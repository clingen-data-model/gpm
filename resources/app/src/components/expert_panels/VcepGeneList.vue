<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Gene/Disease List</h4>
        </header>
        <div class="my-2">
            <table class="border-none" v-if="genes">
                <thead>
                    <tr>
                        <th style="width: 10rem">HGNC Symbol</th>
                        <th>Disease</th>
                        <th style="width: 9rem">Date Approved</th>
                        <th
                            v-if="hasAnyPermission([
                                'ep-applications-manage', 
                                ['application-edit', this.group]
                            ])"
                            style="width: 5rem"
                        ></th>
                    </tr>
                </thead>
                <tbody>
                    <transition-group name="slide-fade-down">
                        <tr v-for="gene in orderedGenes" :key="gene.id">                            
                            <template v-if="!gene.edit">
                                <td>{{gene.gene_symbol}}</td>
                                <td>{{gene.mondo_id}}</td>
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
                                    <input 
                                        type="text" 
                                        v-model="gene.gene_symbol" 
                                        placeholder="gene symbol"
                                        class="w-full"
                                    >
                                </td>
                                <td colspan="2">
                                    <input 
                                        type="text" 
                                        v-model="gene.mondo_id" 
                                        placeholder="Disease name or MonDO ID"
                                        class="w-full"
                                    >
                                </td>
                                <td>
                                    <button class="btn btn-xs" @click="updateCancel(gene)">Cancel</button>
                                    <button class="btn blue btn-xs" @click="updateGene(gene)">Save</button>
                                </td>
                            </template>
                        </tr>
                    </transition-group>
                </tbody>
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
                                <input 
                                    type="text" 
                                    v-model="newGene.gene_symbol" 
                                    placeholder="gene symbol"
                                    class="w-full"
                                >
                            </td>
                            <td colspan="4">
                                <input 
                                    type="text" 
                                    v-model="newGene.mondo_id" 
                                    placeholder="Disease name or MonDO ID"
                                    class="w-full"
                                >
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
import {ref, computed, watch} from 'vue';
import {useStore} from 'vuex';
import EditButton from '@/components/buttons/EditIconButton'
import is_validation_error from '@/http/is_validation_error'
import {isEqual} from 'lodash'

export default {
    name: 'VcepGeneList',
    components: {
        EditButton
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
        const removedGenes = ref([]);
        const addNewGene = () => {
            newGenes.value.push({gene_symbol: null, mondo_id: null});
        }

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

        watch(() => props.group.expert_panel.genes, () => {
                // genes.value = props.group.expert_panel.genes;
                genes.value = [
                    {id: 1, gene_symbol: 'MLTN1', mondo_id: 'MONDO:1234456', date_approved: null},
                    {id: 1, gene_symbol: 'MLTN2', mondo_id: 'MONDO:1234456', date_approved: '2021-09-17T12:23:00'},
                    {id: 1, gene_symbol: 'MLTN3', mondo_id: 'MONDO:1234456', date_approved: null},
                    {id: 1, gene_symbol: 'MLTN4', mondo_id: 'MONDO:1234456', date_approved: null},
                    {id: 1, gene_symbol: 'MLTN5', mondo_id: 'MONDO:1234456', date_approved: '2021-09-17T12:23:00'},
                    {id: 1, gene_symbol: 'MLTN6', mondo_id: 'MONDO:1234456', date_approved: '2021-09-17T12:23:00'},
                    {id: 1, gene_symbol: 'MLTN7', mondo_id: 'MONDO:1234456', date_approved: '2021-09-17T12:23:00'},
                ]
                // return [];
            },
            {immediate: true})
       
        const clearNewGenes = () => {
            newGenes.value = [];
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
            gene.removeTimeout = setTimeout(() => {
                for (let idx = 0; idx < genes.value.length; idx++) {
                    if (genes.value[idx].toDelete) {
                        genes.value.splice(idx, 1);
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

        const save = async () => {
            try {
                if (newGenes.value.length > 0) {
                    await Promise.all(
                        newGenes.value.filter(ng => isEqual(ng, {}))
                        .map(ngene => {
                            // return store.dispatch('groups/addGenes', {uuid: props.group.uuid, ng})
                        })
                    );
                }
                clearNewGenes();
                context.emit('saved')
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        };

        const updateGene = async (gene) => {
            delete(gene.edit);
            console.log('update gene', gene)
        }
        const updateCancel = gene => {
            delete(gene.edit);
            console.log('cancel update for gene')
        }

        const cancel = () => {
            clearNewGenes();
        }

        return {
            genes,
            newGenes,
            orderedGenes,
            errors,
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
