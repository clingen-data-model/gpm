<script setup>
import { computed } from 'vue'
import PublicationManager from '@/components/groups/PublicationManager.vue'
import InputRow from '@/components/forms/InputRow.vue'
import Checkbox from '@/components/forms/Checkbox.vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  version: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  group: { type: Object, required: true },
})
const emit = defineEmits(['update:modelValue'])
const isComplete = computed(() => !!props.modelValue.completed_at)
const periodStart = computed(() => `${Number(props.version.for_year)}-01-01`)
const periodEnd = computed(() => `${Number(props.version.for_year) + 1}-03-31`)
function updateField(field, value) {
  if (isComplete.value) return
  emit('update:modelValue', {
    ...props.modelValue,
    data: { ...props.modelValue.data, [field]: value },
  })
}
const note = computed({
  get: () => props.modelValue.data?.publications_note ?? '',
  set: value => updateField('publications_note', value),
})
const reviewed = computed({
  get: () => props.modelValue.data?.publications_reviewed === true,
  set: value => updateField('publications_reviewed', !!value),
})
</script>

<template>
  <div class="space-y-4">
    <PublicationManager
      v-if="modelValue.id"
      :key="modelValue.id"
      :group="group"
      :readonly="isComplete"
      :snapshots="isComplete ? (modelValue.data?.publications || []) : undefined"
      :period-start="periodStart"
      :period-end="periodEnd"
    />
    <p class="text-gray-500">
      Blue publication dates fall within this Annual Update period {{ formatDate(periodStart) }} to {{ formatDate(periodEnd) }};
      grey dates fall outside it. All visible group publications are included at submission.
    </p>
    <div class="space-y-1">
      <label for="publications-note" class="block font-medium">Notes about publications (optional)</label>
      <textarea id="publications-note" v-model="note" class="w-full rounded-md border p-2" rows="3"
        placeholder="Add any context or comments related to this year's publications..." :disabled="isComplete" />
      <div v-if="errors.publications_note" class="text-xs text-red-600">{{ errors.publications_note }}</div>
    </div>
    <InputRow hide-label vertical :errors="errors.publications_reviewed || []">
      <Checkbox v-model="reviewed" name="publications_reviewed" :disabled="isComplete"
        label="I have reviewed the group's publication list and confirm that it is up to date." />
    </InputRow>
  </div>
</template>
