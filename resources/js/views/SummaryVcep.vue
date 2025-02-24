<template>
    <div>
        <ApplicationsSummaryTable :ep-type-id="2"></ApplicationsSummaryTable>
    </div>
</template>
<script>
import ApplicationsSummaryTable from '@/components/applications/ApplicationsSummaryTable.vue'

export default {
    name: 'GcepsSummary',
    components: {
        ApplicationsSummaryTable
    },
}
</script>