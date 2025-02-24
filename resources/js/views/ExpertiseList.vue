<script>
import ExpertiseCreateForm from '@/components/expertises/ExpertiseCreateForm.vue'
import ExpertiseUpdateForm from '@/components/expertises/ExpertiseUpdateForm.vue'
import sortAndFilter from '@/composables/router_aware_sort_and_filter';

export default {
    name: 'CredentialList',
    components: {
    ExpertiseUpdateForm,
    ExpertiseCreateForm
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
                    name: 'actions',
                    label: '',
                    sortable: false
                }

            ],
            currentItem: {},
            showCreateDialog: false,
            showEditDialog: false,
            showDeleteConfirmation: false,
        }
    },
    computed: {
        items () {
            return this.$store.getters['expertises/items']
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
            return this.items.findIndex(i => i.id === this.currentItem.id);
        }
    },
    methods: {
        edit (item) {
            this.showEditDialog = true;
            this.currentItem = item;
        },
        handleSaved() {
            this.showCreateDialog = false;
            this.showEditDialog = false;

            this.updateItem();
            this.$store.commit("pushSuccess", 'Expertise saved.')
        },
        handleCancel() {
            this.showCreateDialog = false;
            this.showEditDialog = false;
            this.updateItem();
            this.currentItem = {country: {}}
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
                alert('You cannot delete this expertise because it is in use.  Please edit the expertise or remove it from all people using it.')
            }
            try {
                await this.$store.dispatch('expertises/delete', this.currentItem)
                this.showDeleteConfirmation = false;
                this.$store.commit("pushSuccess", `${this.currentItem.name} deleted.`);
            } catch (e) {
                console.error(e);
                throw e;
            }
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
        this.$store.dispatch('expertises/getItems');
    },
}
</script>
<template>
    <div>
        <div class="flex justify-between items-center">
            <h1>Expertises</h1>
            <button class="btn btn-sm" @click="showCreateDialog = true">Add Expertise</button>
        </div>
        <data-table
            paginated
            :data="filteredItems"
            :fields="fields"
            v-model:sort="sort"
            :reset-page-on-data-change="false"
        >
            <template #header>
                <label>
                    Filter:
                    <input type="text" v-model="filter">
                </label>
            </template>
            <template #cell-actions="{item}">
                <dropdown-menu hide-cheveron>
                    <template #label>
                        <button class="btn btn-xs">&hellip;</button>
                    </template>
                    <dropdown-item @click="edit(item)">Edit</dropdown-item>
                    <dropdown-item @click="initDelete(item)">Delete</dropdown-item>
                </dropdown-menu>
            </template>
        </data-table>
        <teleport to="body">
            <modal-dialog v-model="showEditDialog" :title="`Edit ${currentItem.name}`">
                <ExpertiseUpdateForm v-model="currentItem" @saved="handleSaved" @canceled="handleCancel" />
            </modal-dialog>
            <modal-dialog v-model="showDeleteConfirmation" title="Delete Expertise" size="sm">
                <div v-if="currentItem.people_count > 0">
                    <p>You cannot delete an expertise people are using.</p>
                    <p>Either edit this this expertise or remove all people that have claimed it as an expertise.</p>
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
            <modal-dialog v-model="showCreateDialog" title="Add a New Expertise.">
                <ExpertiseCreateForm @saved="handleSaved" @canceled="handleCancel"/>
            </modal-dialog>
        </teleport>
    </div>
</template>
