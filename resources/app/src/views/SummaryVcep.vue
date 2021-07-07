<template>
    <div>
        <applications-summary-table :ep-type-id="2"></applications-summary-table>
    </div>
</template>
<script>
import ApplicationsSummaryTable from '@/components/applications/ApplicationsSummaryTable'

export default {
    name: 'GcepsSummary',
    components: {
        ApplicationsSummaryTable
    },
}
</script>