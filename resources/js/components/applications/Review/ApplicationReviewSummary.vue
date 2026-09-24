<script setup>
defineProps({
  summary: { type: Object, default: null },
  loading: Boolean,
  error: { type: String, default: '' },
})
const categories = [
  { key: 'required_revision', label: 'Required Revision' },
  { key: 'internal_comment', label: 'Internal Comment' },
  { key: 'suggestion', label: 'Suggestion' },
]
const states = [{ key: 'outstanding', label: 'Outstanding' }, { key: 'resolved', label: 'Resolved' }]
</script>

<template>
  <section class="my-4 rounded-xl border bg-white p-4" aria-label="Application Review Summary" :aria-busy="loading">
    <h2>Application Review Summary</h2>
    <p class="mb-3 text-sm text-gray-600">Current application-review comments for this group across Review Rounds.</p>
    <p v-if="loading" role="status">Loading comment counts…</p>
    <p v-else-if="error" role="alert">{{ error }}</p>
    <div v-else-if="summary" class="overflow-x-auto">
      <table class="w-full text-sm">
        <caption class="sr-only">Outstanding and resolved application review comments by category</caption>
        <thead><tr><th scope="col"><span class="sr-only">State</span></th><th v-for="category in categories" :key="category.key" scope="col" class="p-2 text-right">{{ category.label }}</th></tr></thead>
        <tbody>
          <tr v-for="state in states" :key="state.key" class="border-t">
            <th scope="row" class="p-2 text-left">{{ state.label }}</th>
            <td v-for="category in categories" :key="category.key" class="p-2 text-right">{{ summary[category.key][state.key] }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else role="status">Comment counts are not available yet.</p>
  </section>
</template>
