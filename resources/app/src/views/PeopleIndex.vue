<template>
    <div>
        <h1>People</h1>
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
            <!-- <template v-slot:cell-uuid="item">
                <button 
                    class="btn btn-xs" 
                    @click.stop="goToEditPerson(item.item)"
                    v-if="canEdit(item)"
                > 
                    Edit
                </button>
                <span v-else></span>
            </template> -->
        </data-table>
    </div>
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
        })
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
        return SortAndFilter()
    }
}
</script>