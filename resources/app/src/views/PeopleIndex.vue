<template>
    <card title="People">
        <label class="block mb-2" for="filter-input">Filter:&nbsp;<input type="text" v-model="filter" placeholder="filter"></label>
        <data-table 
            :fields="fields" 
            :data="this.people"
            class="width-full"
            :filter-term="filter" 
            :row-click-handler="goToPerson"
            row-class="cursor-pointer"
            v-model:sort="sort"
        >
        </data-table>
    </card>
</template>
<script>
import { mapGetters } from 'vuex'
import SortAndFilter from './../composables/router_aware_sort_and_filter';

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
                    name: 'id',
                    label: '',
                    sortable: false
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
            people: 'people/all'
        })
    },
    methods: {
        goToPerson (person) {
            this.$router.push('/people/'+person.uuid)
        }
    },
    mounted() {
        this.$store.dispatch('people/all')
    },
    setup() {
        return SortAndFilter()
    }
}
</script>