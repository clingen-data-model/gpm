<template>
    <div>
        <note>Admin</note>
        <h1>Invites</h1>
        <pre>currentPage: {{currentPage}}, pageSize: {{pageSize}}, totalItems: {{totalItems}} </pre>
        <data-table
            :data="filteredInvites"
            :fields="fields"
            v-model:sort="tableSort"
            class="text-sm"
            v-remaining-height
            paginated
            :total-items="totalItems"
            :per-page="pageSize"
            :current-page="currentPage"
            @update:currentPage="getPageItems"
        >
            <template v-slot:header>
                <input type="text" v-model="searchTerm" placeholder="filter by name or email" class="mb-2">
            </template>
            <template v-slot:cell-reset="{item}">
                <button class="btn btn-xs" @click="confirmReset(item)" v-if="item.redeemed_at">
                    Reset
                </button>
            </template>
        </data-table>
        <teleport to="body">
            <modal-dialog title="Reset Invite" v-model="showConfirmation">
                <p>You are about to reset the invite for {{resettingInvite.first_name}} {{resettingInvite.last_name}}.</p>
                <p>This cannot be undone.</p>
                <p>Do you want to reset the invite?</p>
                <button-row submit-text="Reset Invite" @submitted="resetInvite(resettingInvite)"></button-row>

            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import {api, isValidationError} from '@/http'

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
            totalItems: 0,
            pageSize: 0,
            currentPage: 1
        }
    },
    computed: {
        filteredInvites () {
            if (!this.searchTerm) {
                return this.invites;
            }
            const filter = new RegExp(`.*${this.searchTerm}.*`, 'i');
            return this.invites.filter(invite => {
                return invite.person && invite.person.first_name && invite.person.first_name.match(filter)
                    || invite.person && invite.person.last_name && invite.person.last_name.match(filter)
                    || `${invite.first_name} ${invite.last_name}`.match(filter)
                    || invite.email.match(filter);
            });
        },
    },
    watch: {
        searchTerm () {
            this.currentPage = 1;
        }
    },
    methods: {
        getPageItems (page) {
            this.currentPage = page;
            this.getInvites();
        },
         async getInvites () {
            const pageResponse = await api.get(`/api/people/invites?page=${this.currentPage}`)
                            .then(rsp => rsp.data);
console.log(pageResponse);
            this.totalItems = pageResponse.meta.total;
            this.currentPage = pageResponse.meta.current_page;
            this.pageSize = pageResponse.meta.per_page;
            this.invites = pageResponse.data;
        }, 
        async resetInvite (invite) {
            try {
                const response = await api.put(`/api/people/invites/${invite.code}/reset`);
                const idx = this.invites.findIndex(i => i.id == invite.id);
                if (idx == -1) {
                    alert('what??')
                }
                this.invites[idx] = response.data;
            } catch (error) {
                if (isValidationError(error)) {
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
    },
    mounted () {
        this.getInvites();
    }
}
</script>