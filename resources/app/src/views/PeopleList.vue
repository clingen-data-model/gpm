<template>
    <div>
        <h1>People</h1>
        <data-table 
            :fields="fields" 
            :data="filteredPeople"
            class="width-full"
            :row-click-handler="goToPerson"
            row-class="cursor-pointer"
            v-model:sort="sort"
            paginated
        >
            <template v-slot:header>
                <label class="block mb-2" for="filter-input">Filter:&nbsp;<input type="text" v-model="filter" placeholder="filter"></label>

            </template>
        </data-table>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import SortAndFilter from './../composables/router_aware_sort_and_filter';
import {pageSize, currentPage, getPageItems} from '@/composables/pagination'

const fields = [
                {
                    name: 'name',
                    sortable: true,
                    type: String
                },
                {
                    name: 'email',
                    sortable: true,
                    type: String
                },
                {
                    name: 'institution.name',
                    label: 'Institution',
                    sortable: true,
                    type: String,
                    resolveSort (item) {
                        return item.institution ? item.institution.name : '';
                    }
                }
            ];

export default {
    components: {
        // DataTable  
    },
    props: {
        
    },
    data() {
        return {
            fields: fields
        }
    },
    computed: {
        ...mapGetters({
            people: 'people/all',
            currentUser: 'currentUser'
        }),
        filteredPeople () {
            if (!this.filter) {
                return this.people;
            }
            const rx = new RegExp(`.*${this.filter}.*`, 'i');
            return this.people.filter(p => {
                return p.name.match(rx)
                    || p.email.match(rx)
                    || (p.institution && p.institution.name.match(rx));
            })
        }
    },
    watch: {
        filter: {
            immediate: true,
            handler () {
                this.currentPage = 0;
            }
        },
        sort: {
            immediate: true,
            handler () {
                this.currentPage = 0;
            }
        }
    },
    methods: {
        goToPerson (person) {
            this.$router.push('/people/'+person.uuid)
        },
        goToEditPerson (person) {
            this.$router.push(`/people/${person.uuid}/edit`)
        },
        canEdit(person) {
            if (this.hasPermission('people-manage')) {
                return true;
            }
            if (this.currentUser.id == person.user_id) {
                return true;
            }

            return false;
        }
    },
    mounted() {
        this.$store.dispatch('people/all', {})
    },
    setup() {
        const {sort, filter} = SortAndFilter();

        return {
            sort, filter,
            pageSize, currentPage, getPageItems
        }
    }
}
</script>