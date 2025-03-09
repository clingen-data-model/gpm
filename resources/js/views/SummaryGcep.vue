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
    <ApplicationsSummaryTable :expert-panel-type-id="1" />
  </div>
</template>