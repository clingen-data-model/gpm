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
        <h1>Mail Log</h1>
        <div class="mb-2">Filter: <input type="text" v-model="filter"></div>
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
                    <li v-for="recipient in item.to" :key="recipient.address">
                        <span v-if="recipient.name">
                            {{recipient.name}} - 
                        </span>
                        {{recipient.address}}
                    </li>
                </ul>
            </template>
            <template v-slot:cell-actions="{item}">
                <div>
                    <button class="btn btn-xs" @click.stop="initResend(item)">Resend</button>
                </div>
            </template>
        </data-table>

        <teleport to="body">
            <modal-dialog v-model="showDetail">
                <mail-detail :mail="currentEmail" @resend="initResend(currentEmail)"/>
            </modal-dialog>
            <modal-dialog title="Resend Email" v-model="showResendDialog">
                <custom-email-form :mail-data="currentEmail" @sent="cleanupResend" @canceled="cleanupResend"></custom-email-form>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import api from '../http/api'
import {formatDateTime} from '@/date_utils'
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
                },
                {
                    name: 'actions',
                    label: '',
                    sortable: false
                }
            ],
            data: [],
            currentEmail: {},
            showResendDialog: false,
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
            console.log(this.currentEmail);
        },
        initResend (item) {
            this.currentEmail = item;
            this.showResendDialog = true;
        },
        cleanupResend () {
            this.currentEmail = {},
            this.showResendDialog = false;
            this.getMailLog();
        }
    },
    mounted() {
        this.getMailLog();
    }
}
</script>   