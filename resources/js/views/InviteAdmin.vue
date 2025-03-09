<script>
import {api, isValidationError} from '@/http'
import {debounce} from 'lodash-es'

export default {
    name: 'InviteAdmin',
    props: {
        
    },
    data () {
        return {

            resettingInvite: {},
            tableSort: {field: 'id', desc: false},
            invites: [],
            showConfirmation: false,
            searchTerm: null,
            fields: [
                {
                    name: 'id',
                    sortable: true,
                    type: Number
                },
                {
                    name: 'person.name',
                    label: 'Name',
                    sortable: true,
                    type: String
                },
                {
                    name: 'person.email',
                    label: 'Email',
                    sortable: true,
                    type: String
                },
                {
                    name: 'redeemed_at',
                    sortable: true,
                    type: Date
                },
                {
                    name: 'inviter',
                    sortable: true,
                    type: String,
                    resolveValue: (invite) => {
                        return invite.inviter ? invite.inviter.name : null
                    }
                },
                {
                    name: 'code',
                    sortable: false,
                    type: String
                },
                {
                    name: 'reset',
                    sortable: false,
                    type: String
                }
            ],
        }
    },
    watch: {
        searchTerm () {
            this.triggerSearch()
        }
    },
    mounted () {
        this.triggerSearch = debounce(() => this.$refs.dataTable.getItems(), 500)
    },
    methods: {

        async itemProvider (currentPage, pageSize, sort, setTotalItems) {
            const params = {
                page: currentPage,
                page_size: pageSize,
                'sort[field]': sort.field.name,
                'sort[dir]': sort.desc ? 'DESC' : 'ASC',
                'where[keyword]': this.searchTerm
            }
            const pageResponse = await api.get(`/api/people/invites`, {params})
                .then(rsp => rsp.data);
            setTotalItems(pageResponse.meta.total);
            return pageResponse.data;
        }, 
        async resetInvite (invite) {
            try {
                const response = await api.put(`/api/people/invites/${invite.code}/reset`);
                const idx = this.invites.findIndex(i => i.id === invite.id);
                if (idx === -1) {
                    // eslint-disable-next-line no-alert
                    alert('what??')
                }
                this.invites[idx] = response.data;
            } catch (error) {
                if (isValidationError(error)) {
                    // eslint-disable-next-line no-alert
                    alert('Problem resetting invite!');
                    return;
                }
            }
            this.showConfirmation = false;
        },
        confirmReset(invite) {
            this.resettingInvite = invite;
            this.showConfirmation = true;
        },
    }
}
</script>
<template>
    <div>
        <note>Admin</note>
        <h1>Invites</h1>
        <data-table
            ref="dataTable"
            v-model:sort="tableSort"
            v-remaining-height
            :data="itemProvider"
            :fields="fields"
            class="text-sm"
            paginated
        >
            <template #header>
                <input v-model="searchTerm" type="text" placeholder="filter by name or email" class="mb-2">
            </template>
            <template #cell-reset="{item}">
                <button v-if="item.redeemed_at" class="btn btn-xs" @click="confirmReset(item)">
                    Reset
                </button>
            </template>
        </data-table>

        <teleport to="body">
            <modal-dialog v-model="showConfirmation" title="Reset Invite">
                <p>You are about to reset the invite for {{ resettingInvite.first_name }} {{ resettingInvite.last_name }}.</p>
                <p>This cannot be undone.</p>
                <p>Do you want to reset the invite?</p>
                <button-row submit-text="Reset Invite" @submitted="resetInvite(resettingInvite)"></button-row>

            </modal-dialog>
        </teleport>
    </div>
</template>