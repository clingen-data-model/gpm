<script>
import sortAndFilter from '@/composables/router_aware_sort_and_filter';
import {getAllInstitutions, deleteInstitution} from '@/forms/institution_form'
import InstitutionApprovalForm from '@/components/institutions/InstitutionApprovalForm.vue'
import InstitutionUpdateForm from '@/components/institutions/InstitutionUpdateForm.vue'
import InstitutionMergeForm from '@/components/institutions/InstitutionMergeForm.vue'

export default {
    name: 'InstitutionList',
    components: {
        InstitutionUpdateForm,
        InstitutionApprovalForm,
        InstitutionMergeForm,
    },
    setup() {
        const {sort, filter} = sortAndFilter({field: 'name', desc: false});

        return {
            sort,
            filter
        }
    },
    data() {
        return {
            items: [],
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    type: Number,
                    sortable: true,
                },
                {
                    name: 'name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'abbreviation',
                    type: String,
                    sortable: true
                },
                {
                    name: 'country.name',
                    label: 'Country',
                    type: String,
                    sortable: true,
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
            currentItem: {country: {}},
            showApproveDialog: false,
            showEditDialog: false,
            showMergeDialog: false,
            showDeleteConfirmation: false,
        }
    },
    computed: {
        filteredItems () {
            if (!this.filter) {
                return this.items;
            }
            const pattern = new RegExp(`.*${this.filter}.*`, 'i');
            return this.items.filter(item => {
                return item.name.match(pattern)
                    || (item.abbreviation && item.abbreviation.match(pattern))
                    || (item.country && item.country.name.match(pattern));
            })
        },
        currentIndex () {
            return this.items.findIndex(i => i.id === this.currentItem.id);
        }
    },
    mounted () {
        this.getInstitutions();
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
        async getInstitutions () {
            this.items = await getAllInstitutions();
        },
        handleSaved() {
            this.showApproveDialog = false;
            this.showEditDialog = false;

            this.updateItem();
            this.currentItem = {country: {}}
            this.$store.commit("pushSuccess", 'Institution saved.')
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
            this.$store.commit("pushSuccess", 'Institution merged.')
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
                // eslint-disable-next-line no-alert
                alert('You cannot delete an institution because it is in use.  Please edit the institution or merge it into another.')
            }
            await deleteInstitution(this.currentItem);
            this.items.splice(this.currentIndex, 1);
            this.showDeleteConfirmation = false;
            this.$store.commit("pushSuccess", `${this.currentItem.name} deleted.`);
        }
    }
}
</script>
<template>
    <div>
        <h1>Institutions</h1>
        <data-table
            v-model:sort="sort"
            paginated
            :data="filteredItems"
            :fields="fields"
            :reset-page-on-data-change="false"
        >
            <template #header>
                <label>
                    Filter:
                    <input v-model="filter" type="text">
                </label>
            </template>
            <template #cell-actions="{item}">
                <dropdown-menu hide-cheveron>
                    <template #label>
                        <button class="btn btn-xs">&hellip;</button>
                    </template>


                    <dropdown-item @click="edit(item)">Edit</dropdown-item>
                    <dropdown-item @click="initApprove(item)">Approve</dropdown-item>
                    <dropdown-item @click="initMerge(item)">Merge into another</dropdown-item>
                    <dropdown-item @click="initDelete(item)">Delete</dropdown-item>
                </dropdown-menu>
            </template>
        </data-table>
        <teleport to="body">
            <modal-dialog v-model="showApproveDialog" :title="`Approve ${currentItem.name}`">
                <InstitutionApprovalForm v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showEditDialog" :title="`Edit ${currentItem.name}`">
                <InstitutionUpdateForm v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showMergeDialog" title="Merge Institutions">
                <InstitutionMergeForm :obsoletes="[currentItem]" @saved="handleMerge" @canceled="showMergeDialog = false" />
            </modal-dialog>
            <modal-dialog v-model="showDeleteConfirmation" title="Delete Institution" size="sm">
                <div v-if="currentItem.people_count > 0">
                    <p>You cannot delete an institution people are using.</p>
                    <p>Either edit this this institution or merge it into another.</p>
                </div>
                <div v-else>
                    You are about to delete the {{ currentItem.name }}
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
