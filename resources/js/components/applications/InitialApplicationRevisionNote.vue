<script setup>
import { computed } from 'vue'
const props = defineProps({ application: { type: Object, required: true } })
const note = computed(() => {
  if (props.application.date_completed || Number(props.application.current_step) !== 1) return null
  const latest = [...(props.application.submissions ?? [])]
    .filter(submission => Number(submission.submission_type_id) === 1
      && !submission.scope_of_work_version_id && submission.data?.context !== 'scope_of_work_revision')
    .sort((a, b) => String(a.created_at ?? '').localeCompare(String(b.created_at ?? '')) || Number(a.id) - Number(b.id)).at(-1)
  return Number(latest?.submission_status_id) === 2 ? latest.response_content : null
})
</script>

<template>
  <div v-if="note" class="my-4 rounded border border-yellow-300 bg-yellow-50 p-4">
    <h3>Revisions requested</h3>
    <p class="whitespace-pre-wrap"><strong>Reviewer note:</strong> {{ note }}</p>
  </div>
</template>
