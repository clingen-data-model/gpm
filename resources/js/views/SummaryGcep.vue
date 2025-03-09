<script>
import ApplicationsSummaryTable from '@/components/applications/ApplicationsSummaryTable.vue'

export default {
    name: 'GcepsSummary',
    components: {
        ApplicationsSummaryTable
    },
}
</script>
<template>
  <div>
    <ApplicationsSummaryTable :expertPanelTypeId="1" />
  </div>
</template>