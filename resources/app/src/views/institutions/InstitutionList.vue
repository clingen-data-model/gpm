<template>
    <div>
        <h1>Institutions</h1>
        <data-table paginated :data="filteredItems" :fields="fields" v-model:sort="sort">
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
                    <dropdown-item @click="initApprove(item)">Approve</dropdown-item>
                    <dropdown-item @click="initMerge(item)">Merge into another</dropdown-item>
                </dropdown-menu>
            </template>
        </data-table>
        <teleport to="body">
            <modal-dialog v-model="showApproveDialog" :title="`Approve ${currentItem.name}`">
                <institution-approval-form v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showEditDialog" :title="`Edit ${currentItem.name}`">
                <institution-update-form v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showMergeDialog" title="Merge Institutions">
                <institution-merge-form :obsoletes="[currentItem]" @saved="handleMerge" @canceled="showMergeDialog = false" />
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import sortAndFilter from '@/composables/router_aware_sort_and_filter';
import {getAllInstitutions} from '@/forms/institution_form'
import InstitutionApprovalForm from '@/components/institutions/InstitutionApprovalForm'
import InstitutionUpdateForm from '@/components/institutions/InstitutionUpdateForm'
import InstitutionMergeForm from '@/components/institutions/InstitutionMergeForm'

export default {
    name: 'InstitutionList',
    components: {
        InstitutionUpdateForm,
        InstitutionApprovalForm,
        InstitutionMergeForm,
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
        async getInstitutions () {
            this.items = await getAllInstitutions();
        },
        handleSaved() {
            this.showApproveDialog = false;
            this.showEditDialog = false;
            // set the item at index and move clear currnet item
            this.updateItem();
            this.currentItem = {country: {}}
        },
        handleCancel() {
            this.showApproveDialog = false;
            this.showEditDialog = false;
            this.updateItem();
            this.currentItem = {country: {}}
        },
        handleMerge() {
            this.getInstitutions(); 
            this.showMergeDialog = false;
        },
        updateItem() {
            if (!this.currentItem.id) {
                return;
            }
            console.log('currentItem', this.currentItem);
            const currentIdx = this.items.findIndex(i => i.id == this.currentItem.id);
            console.log('currentItemIdx', currentIdx);
            if (currentIdx > -1) {
                this.items[currentIdx] = {...this.currentItem}
            }
        }
    },
    setup() {
        const {sort, filter} = sortAndFilter({field: 'name', desc: false});

        return {
            sort,
            filter
        }
    },
    mounted () {
        this.getInstitutions();
    }
}
</script>