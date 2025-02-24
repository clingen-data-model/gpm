<template>
    <div>
        <h1>People</h1>
        <data-table
            :data="itemProvider"
            :fields="fields"
            class="width-full"
            :row-click-handler="goToPerson"
            row-class="cursor-pointer"
            v-model:sort="sort"
            :page-size="20"
            paginated
            ref="dataTable"
        >
            <template v-slot:header>
                <label class="block mb-2" for="filter-input">Filter:&nbsp;<input type="text" v-model="filter" placeholder="filter"></label>
            </template>
        </data-table>
    </div>
</template>
<script>
import { debounce } from 'lodash-es'
import { mapGetters } from 'vuex'
import {api} from '@/http'
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
    data() {
        return {
            fields
        }
    },
    computed: {
        ...mapGetters({
            currentUser: 'currentUser'
        }),
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
            immediate: true,
            handler () {
                if (this.triggerSearch) {
                    this.triggerSearch();
                }
            }
        }
    },
    methods: {
        goToPerson (person) {
            this.$router.push(`/people/${person.uuid}`)
        },
        goToEditPerson (person) {
            this.$router.push(`/people/${person.uuid}/edit`)
        },
        canEdit(person) {
            if (this.hasPermission('people-manage')) {
                return true;
            }
            if (this.currentUser.id === person.user_id) {
                return true;
            }

            return false;
        },
        async itemProvider (currentPage, pageSize, sort, setTotalItems) {
            const params = {
                page: currentPage,
                page_size: pageSize,
                'sort[field]': sort.field.name,
                'sort[dir]': sort.desc ? 'DESC' : 'ASC',
                'where[filterString]': this.filter
            }
            const pageResponse = await api.get(`/api/people`, {params})
                .then(rsp => rsp.data);
            setTotalItems(pageResponse.meta.total);
            return pageResponse.data;
        }
    },
    created() {
        if (this.triggerSearch) {
            this.triggerSearch();
        }
        this.triggerSearch = debounce(() => this.$refs.dataTable.getItems(), 500)
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
