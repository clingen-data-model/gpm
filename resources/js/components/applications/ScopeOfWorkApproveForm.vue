<script setup>
import { computed, ref } from 'vue'
import { api } from '@/http'
import isValidationError from '@/http/is_validation_error'

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

const targetVersion = computed(() => {
  return props.submission.data?.target_version ?? ''
})

const clearForm = () => {
  dateApproved.value = null
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

const save = async () => {
  if (saving.value) {
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const url =
      `/api/groups/${props.group.uuid}` +
      `/application/submission/${props.submission.id}` +
      '/scope-of-work/approve'

    await api.post(url, {
      date_approved: dateApproved.value,
    })

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
        :disabled="saving"
        @click="save"
      >
        {{ saving ? 'Approving...' : 'Approve Scope of Work Revision' }}
      </button>
    </button-row>
  </form-container>
</template>