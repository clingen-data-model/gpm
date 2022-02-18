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
            <template v-slot:cell-actions="{item}">
                <div>
                    <button class="btn btn-xs" @click="resentEmail(item)">Resend</button>
                </div>
            </template>
        </data-table>

        <teleport to="body">
            <modal-dialog v-model="showDetail">
                <dictionary-row label="To" label-class="font-bold" class="mb-1 border-b">
                    <div class="flex-none">
                        <div v-for="(name, address) in currentEmail.to" :key="address">
                            {{address}}
                        </div>
                    </div>
                </dictionary-row>
                <dictionary-row label="From" label-class="font-bold" class="mb-1 border-b">
                    <div v-for="(name, address) in currentEmail.from" :key="address">
                        {{address}}
                    </div>
                </dictionary-row>
                <dictionary-row label="Cc" label-class="font-bold" class="mb-1 border-b">
                    <ul>
                        <li v-for="(name, address) in currentEmail.cc" :key="address">
                            {{address}}
                        </li>
                    </ul>
                </dictionary-row>
                <dictionary-row label="Subject" label-class="font-bold" class="mb-1 border-b">
                    {{currentEmail.subject}}
                </dictionary-row>
                <dictionary-row label="Body" label-class="font-bold" class="mb-1">
                    <div v-html="currentEmail.body" class="email-body w-3/4"></div>
                </dictionary-row>
                <div class="mt-2 border-t pt-2 text-right">
                    <button class="btn " @click="resendEmail(currentEmail)">Resend</button>
                </div>
            </modal-dialog>
            <modal-dialog title="Resend Email">
                <dictionary-row label="To">
                    <ul v-if="group.hasContacts">
                        <li v-for="to in currentEmail.to" :key="contact.email">
                            {{contact.email}}&gt;</router-link>
                    </ul>
                </dictionary-row>
                <dictionary-row label="Cc">
                    <div v-if="email.cc.length > 0">
                        <truncate-expander :value="ccAddresses" :truncate-length="100"></truncate-expander>
                    </div>
                    <div class="text-gray-500" v-else>None</div>
                </dictionary-row>
                <input-row label="Subject">
                    <input type="text" v-model="email.subject" class="w-full">
                </input-row>
                <input-row label="Body">
                    <rich-text-editor  v-model="email.body"></rich-text-editor>
                </input-row>
                <input-row label="Attachments">
                    <input type="file" multiple ref="attachmentsField">
                </input-row>
                <note v-if="emailCced">ClinGen Services will be carbon copied on this email.</note>

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
        },
        initResend (item) {
            this.currentEmail = item;
        },
        resend () {
            
        }
    },
    mounted() {
        this.getMailLog();
    }
}
</script>   