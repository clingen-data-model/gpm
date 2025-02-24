<template>
    <div>
        <ApplicationsSummaryTable :expertPanelTypeId="1"></ApplicationsSummaryTable>
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