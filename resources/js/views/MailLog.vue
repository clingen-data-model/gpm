<script>
import {formatDateTime} from '@/date_utils'
import {debounce} from 'lodash-es'
import sortAndFilter from '../composables/router_aware_sort_and_filter'
import api from '../http/api'

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
                        if (emails.length === 0) {
                            return '';
                        }
                        if (emails.length === 1) {
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
    watch: {
        filter: {
            immediate: true,
            handler () {
                if (this.triggerSearch) {
                    this.triggerSearch();
                }
            }
        },
        sort: {
            deep: true,
            immediate: true,
            handler () {
                if (this.triggerSearch) {
                    this.triggerSearch();
                }
            }
        }
    },
    methods: {
        async getPage (currentPage, pageSize, sort, setTotalItems) {
            try {
                const params = {
                    page: currentPage,
                    page_size: pageSize,
                    'sort[field]': sort.field.name,
                    'sort[dir]': sort.desc ? 'DESC' : 'ASC',
                    'where[filterString]': this.filter
                }
                const pageResponse = await api.get(`/api/mail-log`, {params})
                    .then(rsp => rsp.data);

                setTotalItems(pageResponse.total);
                return pageResponse.data;
            } catch (error) {
                // eslint-disable-next-line no-alert
                alert(error);
            }
        },
        showMailDetail (item) {
            this.currentEmail = item;
            this.showDetail = true;
        },
        initResend (item) {
            this.currentEmail = item;
            this.showResendDialog = true;
        },
        cleanupResend () {
            this.currentEmail = {}
            this.showResendDialog = false;
            this.$refs.dataTable.getItems()
        }
    },
    created() {
        this.triggerSearch = debounce(() => this.$refs.dataTable.getItems(), 500)
    },
}
</script>
<template>
    <div>
        <h1>Mail Log</h1>
        <data-table
            :fields="fields"
            :data="getPage"
            row-class="cursor-pointer"
            :row-click-handler="showMailDetail"
            v-model:sort="sort"
            :page-size="20"
            paginated
            ref="dataTable"
        >
            <template #header>
                <div class="mb-2">Filter: <input type="text" v-model="filter"></div>
            </template>
            <template #cell-to="{item}">
                <ul>
                    <li v-for="recipient in item.to" :key="recipient.address">
                        <span v-if="recipient.name">
                            {{ recipient.name }} -
                        </span>
                        {{ recipient.address }}
                    </li>
                </ul>
            </template>
            <template #cell-actions="{item}">
                <div>
                    <button class="btn btn-xs" @click.stop="initResend(item)" v-if="hasPermission('people-manage')">Resend</button>
                </div>
            </template>
        </data-table>

        <teleport to="body">
            <modal-dialog v-model="showDetail">
                <mail-detail :mail="currentEmail" @resend="initResend(currentEmail)" v-if="hasPermission('people-manage')"/>
            </modal-dialog>
            <modal-dialog title="Resend Email" v-model="showResendDialog">
                <custom-email-form :mail-data="currentEmail" @sent="cleanupResend" @canceled="cleanupResend"></custom-email-form>
            </modal-dialog>
        </teleport>
    </div>
</template>
<style lang="postcss">
.email-body p{
    @apply mb-4;
}
.email-body a {
    @apply text-blue-700 underline;
}
</style>
