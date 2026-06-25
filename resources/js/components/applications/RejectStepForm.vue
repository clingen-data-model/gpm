<script setup>
import { computed, ref } from 'vue'
import { api } from '@/http'
import isValidationError from '@/http/is_validation_error'
import UserDefinedMailForm from '@/components/forms/UserDefinedMailForm.vue'

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

const notifyContacts = ref(true)
const errors = ref({})
const saving = ref(false)
const emptyEmail = () => ({
  subject: '',
  body: '',
  cc: [],
  to: [],
  files: [],
})
const email = ref(emptyEmail())
const submissionText = computed(() => {
  if (saving.value) { return 'Requesting Revisions...'; }
  return `Request Revisions${notifyContacts.value ? ' and notify' : ''}`
})

const clearForm = () => {
  notifyContacts.value = true
  email.value = emptyEmail()
  errors.value = {}
  saving.value = false
}

const cancel = () => {
  if (saving.value) {
    return
  }

  clearForm()
  emit('canceled')
}

const getEmailTemplate = async () => {
  if (!notifyContacts.value) { return }
  const response = await api.get(`/api/email-drafts/groups/${props.group.uuid}`, {
      params: { templateClass: 'App\\Mail\\UserDefinedMailTemplates\\ApplicationRevisionRequestTemplate', },
    }
  )
   
  email.value = {
    ...response.data,
    files: [],
  }
}

const handleNotifyContactsChange = () => {
  if (notifyContacts.value) {
    getEmailTemplate()
  }
}

const save = async () => {
  if (saving.value) {
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const data = {
      notify_contacts: notifyContacts.value,
      subject: email.value.subject,
      body: email.value.body,
      attachments: email.value.files,
    }

    const url = `/api/groups/${props.group.uuid}/application/submission/${props.submission.id}/rejection`

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
  getEmailTemplate,
})
</script>
<template>
  <form-container>
    <dictionary-row label="">
      <div>
        <label class="text-sm">
          <input
            v-model="notifyContacts"
            type="checkbox"
            :value="true"
            :disabled="saving"
            @change="handleNotifyContactsChange"
          >
          <div>Send notification email to contacts</div>
        </label>
      </div>
    </dictionary-row>

    <transition name="slide-fade-down">
      <UserDefinedMailForm
        v-show="notifyContacts"
        v-model="email"
      />
    </transition>

    <button-row
      :submit-text="submissionText"
      :disabled="saving"
      @canceled="cancel"
      @submitted="save"
    />
  </form-container>
</template>