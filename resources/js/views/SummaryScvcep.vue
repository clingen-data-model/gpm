<script>
import ApplicationsSummaryTable from '@/components/applications/ApplicationsSummaryTable.vue'

export default {
    name: 'ScvcepsSummary',
    components: {
        ApplicationsSummaryTable
    },
}
</script>
<template>
  <div>
    <ApplicationsSummaryTable :ep-type-id="3" />
  </div>
</template>