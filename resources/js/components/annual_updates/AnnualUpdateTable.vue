<script>
import {api} from '@/http'
import setupRouterSortAndFilter from '@/composables/router_aware_sort_and_filter'

export default {
    name: 'AnnualUpdateTeable',
    props: {
        items: {
            type: Array,
            required: true
        }
    },
    setup() {
        const {sort, filter} = setupRouterSortAndFilter({field: 'expert_panel.display_name', desc: false});

        return {
            sort,
            filter
        }
    },
    data() {
        return {
            fields: [
                {
                    name: 'id',
                    sortable: true,
                    label: 'ID'
                },
                {
                    name: 'expert_panel.display_name',
                    label: 'Expert Panel',
                    sortable: true
                },
                {
                    name: 'submitter.person.name',
                    label: 'Submitter',
                    sortable: true
                },
                {
                    name: 'completed_at',
                    label: 'Completed',
                    sortable: true,
                    resolveValue: (item) => {
                        return item.completed_at ? this.formatDate(item.completed_at) : null;
                    }
                },
                {
                    name: 'action',
                    label: '',
                    sortable: false

                }
            ],
            // completedFilter: null
            
        }
    },
    computed: {
        completedFilter: {
            get () {
                return this.$route.query.completed || null;
            },
            set (value) {
                const currentQuery = this.$route.query;
                const currentPath = this.$route.path;
        
                let updatedQuery = {...currentQuery};
        
                if (!value) {
                    delete updatedQuery.completed;
                } else {
                    updatedQuery = {...currentQuery, ...{'completed': value} };
                }
        
                this.$router.replace({path: currentPath, query: updatedQuery})
            }
        },
        filteredItems () {
            let result = JSON.parse(JSON.stringify(this.items));
            if (this.filter != null) {
                const pattern = new RegExp(`.*${this.filter}.*`, 'i');
                result = this.items.filter(i => {
                    return i.expert_panel.display_name.match(pattern)
                        || (i.submitter && i.submitter.person && i.submitter.person.name.match(pattern));
                });
            }

            if (this.completedFilter != null) {
                result = result.filter(i => {
                    if (this.completedFilter === 1) {
                        return i.completed_at !== null;
                    }
                    return i.completed_at == null;
                });
            }
            return result
        },
    },
    methods: {
        exportData () {
            const annual_update_ids = this.filteredItems.map(i => i.id);
            api.post(`/api/annual-updates/export`, {annual_update_ids})
                .then(response => {
                    const a = document.createElement('a');
                    a.style.display = "none";
                    document.body.appendChild(a);

                    a.href = window.URL.createObjectURL( new Blob([response.data, { type: 'text/csv' }]));

                    a.setAttribute('download', 'annual_updates.csv');
                    a.click();

                    document.body.removeChild(a);
                });
        }
    }
}
</script>
<template>
    <div>
        <div class="flex mb-2 items-end justify-between">
            <div class="flex space-x-4 items-end">
                <input 
                    v-model="filter" 
                    placeholder="EP name, submitter name"
                    class="w-60"
                >
                <select v-model="completedFilter" class="flex radio-group">
                    <option :value="null">Any</option>
                    <option :value="2">Only Pending</option>
                    <option :value="1">Only Completed</option>
                </select>
            </div>
            <button class="btn btn-xs" @click="exportData">Export Data</button>
        </div>

        <data-table v-model:sort="sort" :data="filteredItems" :fields="fields">
            <template #cell-action="{item}">
                <router-link :to="{name: 'AnnualUpdateDetail', params: {id: item.id}}">
                    view
                </router-link>
            </template>
        </data-table>

    </div>
</template>