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
    <ApplicationsSummaryTable :ep-type-id="2" />
  </div>
</template>