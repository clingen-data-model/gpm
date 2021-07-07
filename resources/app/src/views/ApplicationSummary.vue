<template>
    <div>
        <h1>Expert Panel Application Summary</h1>
        <div class="mb-2 mt-4">
            <div class="tabs">
                <router-link :to="{name: 'VcepsSummary'}" class="tab">VCEPS</router-link>
                <router-link :to="{name: 'GcepsSummary'}" class="tab">GCEPS</router-link>
            </div>
            <div class="p-4 border rounded-tr-lg rounded-b-lg bg-white">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>
<script>
import ApplicationsTable from '../components/applications/ApplicationsTable.vue'
export default {
  components: { ApplicationsTable },
    name: 'ComponentName',
    props: {
        
    },
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>
<style lang="postcss" scoped>
.tabs {
    @apply flex space-x-2;
}

.tabs .tab {
    @apply border border-b-0 px-4 py-1 rounded-t-lg bg-gray-200 -mb-px;
}

.tabs .tab.router-link-active,
.tabs .tab.active {
    @apply bg-white no-underline;
}

</style>