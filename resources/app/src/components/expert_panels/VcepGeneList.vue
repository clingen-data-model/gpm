<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Gene/Disease List</h4>
            <router-link :to="{name: 'AddGene'}" class="btn btn-xs">Add Gene/Disease</router-link>
        </header>
        <div class="mt-2 bg-gray-50">
            <table class="border-none" v-if="genes">
                <thead>
                    <tr>
                        <th>HGNC Symbol</th>
                        <th>Disease</th>
                        <th>Date Approved</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="gene in genes" :key="gene.id">
                        <td>{{gene.gene_symbol}}</td>
                        <td>{{gene.mondo_id}}</td>
                        <td>{{gene.date_approved}}</td>
                        <td><edit-button></edit-button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script>
import {ref, computed} from 'vue';
import {useStore} from 'vuex';
import EditButton from '@/components/buttons/EditIconButton'
import is_validation_error from '@/http/is_validation_error'

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
        const errors = ref({});
        const genes = computed(() => {
            if (props.group.expert_panel.genes) {
                return props.group.expert_panel.genes;
            }
            return [{id: 1, gene_symbol: 'MLTN1', mondo_id: 'MONDO:1234456', date_approved: null}]
            return [];
        })
        
        const save = async () => {
            try {
                // await store.dispatch('groups/geneListUpdate', {uuid: props.group.uuid, genes});
                context.emit('saved')
            } catch (error) {
                console.log(error);
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        };

        return {
            genes,
            errors,
            save,
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
