<script setup>
defineProps({ summary: { type: Object, required: true }, step: { type: Number, required: true } })
const categories = [
  { key: 'required_revision', label: 'Required Revision' },
  { key: 'internal_comment', label: 'Internal Comment' },
  { key: 'suggestion', label: 'Suggestion' },
]
const states = [{ key: 'outstanding', label: 'Outstanding' }, { key: 'resolved', label: 'Resolved' }]
</script>
<template>
  <div class="min-w-0 overflow-x-auto">
    <h3>Step {{ step }} Review</h3>
      <table class="w-full text-sm">
        <caption class="sr-only">Step {{ step }} Review: Outstanding and resolved application review comments by category</caption>
        <thead><tr><th scope="col"><span class="sr-only">State</span></th><th v-for="category in categories" :key="category.key" scope="col" class="p-2 text-right">{{ category.label }}</th></tr></thead>
        <tbody>
          <tr v-for="state in states" :key="state.key" class="border-t">
            <th scope="row" class="p-2 text-left">{{ state.label }}</th>
            <td v-for="category in categories" :key="category.key" class="p-2 text-right">{{ summary[category.key][state.key] }}</td>
          </tr>
        </tbody>
      </table>
  </div>
</template>
