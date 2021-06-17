<style lang="postcss">
.email-body p{
    @apply mb-4;
}
.email-body a {
    @apply text-blue-700 underline
}
</style>
<template>
    <div>
        <h1 class="text-xl font-bold pb-2 mb-4 border-b">Mail Log</h1>
        Filter: <input type="text" v-model="filter">
        <data-table 
            :fields="fields" 
            :data="data"
            :filter-term="filter" 
            row-class="cursor-pointer"
            :row-click-handler="showMailDetail"
            v-model:sort="sort"
        >
            <template v-slot:cell-to="{item}">
                <ul>
                    <li v-for="(name,email) in item.to" :key="email">
                        {{email}}
                    </li>
                </ul>
            </template>
        </data-table>

        <modal-dialog v-model="showDetail">
            <dictionary-row label="To" label-class="font-bold" class="mb-1 border-b">
                <div v-for="(name, address) in currentEmail.to" :key="address">
                    {{address}}
                </div>
            </dictionary-row>
            <dictionary-row label="From" label-class="font-bold" class="mb-1 border-b">
                <div v-for="(name, address) in currentEmail.from" :key="address">
                    {{address}}
                </div>
            </dictionary-row>
            <dictionary-row label="Subject" label-class="font-bold" class="mb-1 border-b">
                {{currentEmail.subject}}
            </dictionary-row>
            <dictionary-row label="Body" label-class="font-bold" class="mb-1">
                <div v-html="currentEmail.body" class="email-body w-3/4"></div>
            </dictionary-row>
        </modal-dialog>
    </div>
</template>
<script>
import api from '../http/api'
import {formatDateTime} from '../date_utils'
import sortAndFilter from '../composables/router_aware_sort_and_filter'

export default {
    props: {
        
    },
    setup() {
        const {sort, filter} = sortAndFilter({field: 'created_at', desc: true});

        return {
            sort,
            filter,
        }
    },
    data() {
        return {
            showDetail: false,
            fields: [
                {        
                    name: 'to',
                    sortable: true,
                    type: Array,
                    resolveValue(item) {
                        const emails = Object.keys(item.to);
                        if (emails.length == 0) {
                            return '';
                        }
                        if (emails.length == 1) {
                            return emails[0];
                        }
                        return emails.join('; ');
                    }
                },
                {
                    name: 'subject',
                    sortable: true,
                    type: String
                },
                {
                    name: 'created_at',
                    label: 'Sent at',
                    sortable: true,
                    resolveValue(item) {
                        return formatDateTime(item.created_at)
                    },
                    resolveSort(item) {
                        return item.created_at;                        
                    },
                    type: String
                }
            ],
            data: [],
            currentEmail: {}
        }
    },
    computed: {

    },
    methods: {
        async getMailLog() {
            try {
                await api.get('/api/mail-log')
                    .then(response => {
                        this.data = response.data;
                    });
            } catch (error) {
                alert(error);
            }
        },
        showMailDetail (item) {
            this.currentEmail = item;
            this.showDetail = true;
        }
    },
    mounted() {
        this.getMailLog();
    }
}
</script>   