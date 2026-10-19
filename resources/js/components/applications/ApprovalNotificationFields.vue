<script setup>
import { ref, watch } from 'vue'
import { api } from '@/http'
import UserDefinedMailForm from '@/components/forms/UserDefinedMailForm.vue'

const props = defineProps({
  group: { type: Object, required: true },
  step: { type: Number, required: true },
  disabled: Boolean,
})
const notifyContacts = defineModel('notifyContacts', { type: Boolean, default: false })
const email = defineModel('email', { type: Object, required: true })
const pending = defineModel('pending', { type: Boolean, default: false })
const error = ref('')
const retry = ref(0)
const templates = {
  1: 'InitialApprovalMailTemplate', 2: 'SpecificationDraftMailTemplate',
  3: 'SpecificationPilotMailTemplate', 4: 'SustainedCurationApprovalMailTemplate',
}
watch([notifyContacts, retry], async ([enabled], _, onCleanup) => {
  let current = true
  onCleanup(() => { current = false })
  error.value = ''
  pending.value = false
  if (!enabled || email.value.body) return
  pending.value = true
  try {
    const response = await api.get(`/api/email-drafts/groups/${props.group.uuid}`, {
      params: { templateClass: `App\\Mail\\UserDefinedMailTemplates\\${templates[props.step]}` },
    })
    if (!current) return
    email.value = { ...response.data, files: [] }
    pending.value = false
  } catch {
    if (current) error.value = 'The approval email could not be loaded.'
  }
})
</script>
<template>
  <dictionary-row label="">
    <label class="text-sm">
      <input v-model="notifyContacts" type="checkbox" :disabled="disabled">
      <div>Send notification email to contacts</div>
    </label>
  </dictionary-row>
  <p v-if="notifyContacts && error" role="alert">
    {{ error }} <button type="button" class="btn" @click="retry++">Retry</button>
  </p>
  <p v-else-if="notifyContacts && pending">Loading approval email...</p>
  <UserDefinedMailForm v-else-if="notifyContacts" v-model="email" />
</template>
