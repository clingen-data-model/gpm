<script>
import api from '../http/api'
import {debounce} from 'lodash-es'
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
    created() {
        this.triggerSearch = debounce(() => this.$refs.dataTable.getItems(), 500)
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
}
</script>
<template>
    <div>
        <h1>Mail Log</h1>
        <data-table
            ref="dataTable"
            v-model:sort="sort"
            :fields="fields"
            :data="getPage"
            row-class="cursor-pointer"
            :row-click-handler="showMailDetail"
            :page-size="20"
            paginated
        >
            <template #header>
                <div class="mb-2">Filter: <input v-model="filter" type="text"></div>
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
                    <button v-if="hasPermission('people-manage')" class="btn btn-xs" @click.stop="initResend(item)">Resend</button>
                </div>
            </template>
        </data-table>

        <teleport to="body">
            <modal-dialog v-model="showDetail">
                <mail-detail v-if="hasPermission('people-manage')" :mail="currentEmail" @resend="initResend(currentEmail)"/>
            </modal-dialog>
            <modal-dialog v-model="showResendDialog" title="Resend Email">
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
