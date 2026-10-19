<script setup>
import ReviewSummaryTable from './ReviewSummaryTable.vue'
defineProps({
  summary: { type: Object, default: null },
  loading: Boolean,
  error: { type: String, default: '' },
})
</script>

<template>
  <section class="my-4 rounded-xl border bg-white p-4" aria-label="Application Review Summary" :aria-busy="loading">
    <h2>Application Review Summary</h2>
    <p class="mb-3 text-sm text-gray-600">Current application-review comments for this group across Review Rounds.</p>
    <p v-if="loading" role="status">Loading comment counts…</p>
    <p v-else-if="error" role="alert">{{ error }}</p>
    <div v-else-if="summary" class="grid grid-cols-1 gap-6" :class="{ 'lg:grid-cols-2': summary.step4Summary }">
      <ReviewSummaryTable :summary="summary.step1Summary" :step="1" />
      <ReviewSummaryTable v-if="summary.step4Summary" :summary="summary.step4Summary" :step="4" />
    </div>
    <p v-else role="status">Comment counts are not available yet.</p>
  </section>
</template>
