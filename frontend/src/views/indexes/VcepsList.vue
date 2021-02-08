<template>
    <div>
        <div class="mb-1">
            <label>Filter: <input type="text" class="sm" v-model="filter" placeholder="filter"></label>
        </div>
        <data-table :data="data" :fields="fields" :filter-term="filter"></data-table>
        <application-list></application-list>
    </div>
</template>
<script>
import ApplicationList from '../../components/applications/ApplicationList'

export default {
    components: {
        ApplicationList
    },
    props: {
        
    },
    data() {
        return {
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    type: Number,
                    sortable: true
                },
                {
                    name: 'name',
                    label: 'Name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'email',
                    label: 'email',
                    type: String,
                    sortable: true
                },
                {
                    name: 'birthday',
                    label: 'DOB',
                    type: Date,
                    sortable: false
                }
            ],
            data: [],
        }
    },
    computed: {
        filter: {
            set(value) {
                let currentQuery = this.$route.query;
                let currentPath = this.$route.path;

                this.$router.replace({path: currentPath, query: {...currentQuery, ...{filter: value}}})
            },
            get() {
                return this.$route.query.filter
            },
            immediate: true
        }
    },
    methods: {

    }
}
</script>