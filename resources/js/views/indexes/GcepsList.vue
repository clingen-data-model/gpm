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
<template>
    <div>
        <ApplicationsTable :ep-type-id="1"></ApplicationsTable>
    </div>
</template>