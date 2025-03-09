<script>
import ApplicationsTable from '@/components/applications/ApplicationsTable.vue'

export default {
    components: {
        ApplicationsTable
    },
}
</script>
<template>
    <div>
        <ApplicationsTable :ep-type-id="2"></ApplicationsTable>
    </div>
</template>