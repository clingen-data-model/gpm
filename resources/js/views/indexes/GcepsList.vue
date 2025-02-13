<template>
    <div>
        <applications-table :ep-type-id="1"></applications-table>
    </div>
</template>
<script>
import ApplicationsTable from '@/components/applications/ApplicationsTable.vue'

export default {
    components: {
        ApplicationsTable
    },
    props: {
        
    },
    data() {
        return {
        }
    },
}
</script>