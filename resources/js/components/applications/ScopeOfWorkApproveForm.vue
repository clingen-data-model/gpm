<script setup>
import { computed, ref } from 'vue'
import { api } from '@/http'
import isValidationError from '@/http/is_validation_error'
import ApprovalNotificationFields from './ApprovalNotificationFields.vue'

const props = defineProps({
  group: {
    type: Object,
    required: true,
  },
  submission: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'canceled',
  'saved',
])

const dateApproved = ref(null)
const errors = ref({})
const saving = ref(false)
const notifyContacts = ref(false)
const emailPending = ref(false)
const email = ref({ subject: '', body: '', files: [], cc: [], to: [] })

const targetVersion = computed(() => {
  return props.submission.data?.target_version ?? ''
})

const clearForm = () => {
  dateApproved.value = null
  errors.value = {}
  saving.value = false
  notifyContacts.value = false
  email.value = { subject: '', body: '', files: [], cc: [], to: [] }
}

const cancel = () => {
  if (saving.value) {
    return
  }

  clearForm()
  emit('canceled')
}

const save = async () => {
  if (saving.value || emailPending.value) {
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const url =
      `/api/groups/${props.group.uuid}` +
      `/application/submission/${props.submission.id}` +
      '/scope-of-work/approve'

    const data = new FormData()
    data.append('date_approved', dateApproved.value ?? '')
    data.append('notify_contacts', notifyContacts.value ? '1' : '0')
    if (notifyContacts.value) {
      data.append('subject', email.value.subject)
      data.append('body', email.value.body)
      for (const file of email.value.files ?? []) data.append('attachments[]', file)
    }
    await api.post(url, data)

    clearForm()
    emit('saved')
  } catch (error) {
    if (isValidationError(error)) {
      errors.value = error.response.data.errors
      return
    }

    throw error
  } finally {
    saving.value = false
  }
}

defineExpose({
  clearForm,
})
</script>

<template>
  <form-container>
    <h2>
      Approve Scope of Work Revision
      <span v-if="targetVersion">
        {{ targetVersion }}
      </span>
    </h2>

    <input-row
      v-model="dateApproved"
      type="date"
      label="Date Approved"
      :errors="errors.date_approved"
    />

    <ApprovalNotificationFields
      v-model:notify-contacts="notifyContacts"
      v-model:email="email"
      v-model:pending="emailPending"
      :group="group"
      :step="Number(submission.data?.approval_step ?? 1)"
      :disabled="saving"
    />

    <static-alert
      v-if="errors.submission"
      variant="danger"
    >
      {{ errors.submission[0] }}
    </static-alert>

    <button-row>
      <button
        class="btn"
        :disabled="saving"
        @click="cancel"
      >
        Cancel
      </button>

      <button
        class="btn blue"
        :disabled="saving || emailPending"
        @click="save"
      >
        {{ saving ? 'Approving...' : 'Approve Scope of Work Revision' }}
      </button>
    </button-row>
  </form-container>
</template>
