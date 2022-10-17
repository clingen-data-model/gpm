<template>
    <div>
        <h1>Credentials</h1>
        <data-table
            paginated
            :data="filteredItems"
            :fields="fields"
            v-model:sort="sort"
            :reset-page-on-data-change="false"
        >
            <template v-slot:header>
                <label>
                    Filter:
                    <input type="text" v-model="filter">
                </label>
            </template>
            <template v-slot:cell-actions="{item}">
                <dropdown-menu hide-cheveron>
                    <template v-slot:label>
                        <button class="btn btn-xs">&hellip;</button>
                    </template>
                    <dropdown-item @click="edit(item)">Edit</dropdown-item>
                    <dropdown-item v-if="!item.approved" @click="initApprove(item)">Approve</dropdown-item>
                    <dropdown-item @click="initMerge(item)">Merge into another</dropdown-item>
                    <dropdown-item @click="initDelete(item)">Delete</dropdown-item>
                </dropdown-menu>
            </template>
        </data-table>
        <teleport to="body">
            <modal-dialog v-model="showApproveDialog" :title="`Approve ${currentItem.name}`">
                <CredentialsApprovalForm v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showEditDialog" :title="`Edit ${currentItem.name}`">
                <CredentialUpdateForm v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showMergeDialog" title="Merge Credentials">
                <CredentialMergeForm
                    v-if="currentItem.id"
                    :obsoletes="currentItem"
                    :credentials="items"
                    @saved="handleMerge"
                    @canceled="showMergeDialog = false"
                />
            </modal-dialog>
            <modal-dialog v-model="showDeleteConfirmation" title="Delete Credential" size="sm">
                <div v-if="currentItem.people_count > 0">
                    <p>You cannot delete an credential people are using.</p>
                    <p>Either edit this this credential or merge it into another.</p>
                </div>
                <div v-else>
                    You are about to delete the {{currentItem.name}}
                    <button-row
                        submit-text="Delete"
                        @submitted="deleteItem"
                        @canceled="showDeleteConfirmation = false"
                    ></button-row>
                </div>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import {api} from '@/http'
import sortAndFilter from '@/composables/router_aware_sort_and_filter';
import CredentialUpdateForm from '@/components/credentials/CredentialUpdateForm.vue'
import CredentialsApprovalForm from '../components/credentials/CredentialsApprovalForm.vue';
import CredentialMergeForm from '../components/credentials/CredentialMergeForm.vue';

export default {
    name: 'CredentialList',
    components: {
    CredentialUpdateForm,
    CredentialsApprovalForm,
    CredentialMergeForm
},
    data() {
        return {
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    type: Number,
                    sortable: true,
                    class: 'w-12'
                },
                {
                    name: 'name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'people_count',
                    label: '# People',
                    type: Number,
                    sortable: true
                },
                {
                    name: 'approved',
                    type: Boolean,
                    sortable: true,
                    resolveValue (item) {
                        return item.approved ? 'Approved' : null;
                    },
                    resolveSort (item) {
                        return item.approved;
                    }
                },
                {
                    name: 'actions',
                    label: '',
                    sortable: false
                }

            ],
            currentItem: {},
            showApproveDialog: false,
            showEditDialog: false,
            showMergeDialog: false,
            showDeleteConfirmation: false,
        }
    },
    computed: {
        items () {
            return this.$store.getters['credentials/items']
        },
        filteredItems () {
            if (!this.filter) {
                return this.items;
            }
            const pattern = new RegExp(`.*${this.filter}.*`, 'i');
            return this.items.filter(item =>  item.name.match(pattern)
                                        || (item.abbreviation && item.abbreviation.match(pattern))
                                        || (item.country && item.country.name.match(pattern)))
        },
        currentIndex () {
            return this.items.findIndex(i => i.id == this.currentItem.id);
        }
    },
    methods: {
        edit (item) {
            this.showEditDialog = true;
            this.currentItem = item;
        },
        initApprove (item) {
            this.showApproveDialog = true;
            this.currentItem = item;
        },
        initMerge (item) {
            this.currentItem = item;
            this.showMergeDialog = true;
        },
        handleSaved() {
            this.showApproveDialog = false;
            this.showEditDialog = false;

            this.updateItem();
            this.$store.commit("pushSuccess", 'Credential saved.')
        },
        handleCancel() {
            this.showApproveDialog = false;
            this.showEditDialog = false;
            this.updateItem();
            this.currentItem = {country: {}}
        },
        handleMerge() {
            this.items.splice(this.currentIndex, 1);
            this.showMergeDialog = false;
            this.$store.commit("pushSuccess", 'Credential merged.')
            this.currentItem = {};
        },
        updateItem() {
            if (!this.currentItem.id) {
                return;
            }

            if (this.currentIndex > -1) {
                this.items[this.currentIndex] = {...this.currentItem}
            }
        },
        initDelete (item) {
            this.showDeleteConfirmation = true;
            this.currentItem = item;
        },
        async deleteItem() {
            if (!this.currentItem.id) {
                return;
            }

            if (this.currentItem.people_count > 0) {
                alert('You cannot delete an credential because it is in use.  Please edit the credential or merge it into another.')
            }
            await this.$store.dispatch('credentials/delete', this.currentItem)
            this.showDeleteConfirmation = false;
            this.$store.commit("pushSuccess", `${this.currentItem.name} deleted.`);
        },
    },
    setup() {
        const {sort, filter} = sortAndFilter({field: 'name', desc: false});

        return {
            sort,
            filter
        }
    },
    mounted () {
        this.$store.dispatch('credentials/getItems');
    },
}
</script>
