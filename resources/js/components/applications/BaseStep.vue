<script setup>
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import { formatDate } from '@/date_utils'
import ApplicationLog from '@/components/applications/ApplicationLog.vue'
import StepControls from '@/components/applications/StepControls.vue'
import RemoveButton from '@/components/buttons/RemoveButton.vue'
import isValidationError from '@/http/is_validation_error'

const props = defineProps({
  title: {
    type: String,
    default: 'YOU SHOULD SET A TITLE',
  },
  step: {
    type: Number,
    required: true,
  },
  documentName: {
    type: String,
    default: 'Set a document-type attribute if you don\'t use the "document" slot',
  },
  documentType: {
    type: Number,
    default: 1,
  },
  documentGetsReviewed: {
    type: Boolean,
    default: true,
  },
  approveButtonLabel: {
    type: String,
    default: 'Set "approve-button-label" if not overriding slot "approve-button"',
  },
})

const emit = defineEmits([
  'documentUploaded',
  'approved',
  'updated',
])

const store = useStore()
const editApprovalDate = ref(false)
const newApprovalDate = ref(null)
const errors = ref({})

const group = computed(() => {
  return store.getters['groups/currentItemOrNew']
})

const application = computed(() => {
  return group.value?.expert_panel
})

const activeScopeOfWorkSubmission = computed(() => {
  const submissions = application.value?.submissions ?? []

  return [...submissions].filter(submission => {
      const statusId = Number.parseInt(submission.submission_status_id)
      return submission.data?.context === 'scope_of_work_revision' && [1, 2, 3].includes(statusId)
    })
    .sort((a, b) => Number(b.id) - Number(a.id))[0] ?? null
})

const hasScopeOfWorkSubmissionForThisStep = computed(() => {
  const submission = activeScopeOfWorkSubmission.value
  if (!submission) {
    return false
  }
  const approvalStep = Number.parseInt(
    submission.data?.approval_step ?? 1
  )
  return approvalStep === Number.parseInt(props.step)
})

const showStepControls = computed(() => {
  if (!application.value) {
    return false
  }

  // When an active SoW revision exists, show its controls and submission information only on its configured approval step.
  if (activeScopeOfWorkSubmission.value) {
    return hasScopeOfWorkSubmissionForThisStep.value
  }

  // Preserve the normal initial-application behavior.
   
  return !application.value.stepIsApproved(props.step)
})

const isCurrentStep = computed(() => {
  return Number.parseInt(props.step) === Number.parseInt(application.value?.current_step)
})

const dateApproved = computed(() => {
  if (!application.value) { return null; }
  const approvalDate = application.value.approvalDateForStep(props.step)
  return approvalDate ? formatDate(approvalDate) : null
})

const goToPrintable = () => {
  window.open(`/groups/${group.value.uuid}/application/review`)
}

const handleUpdated = () => {
  emit('updated')
}

const initEditApprovalDate = () => {
  editApprovalDate.value = true
  newApprovalDate.value = dateApproved.value
}

const updateApprovalDate = async () => {
  errors.value = {}
  try {
    await store.dispatch('groups/updateApprovalDate', {
      group: group.value,
      dateApproved: newApprovalDate.value,
      step: props.step,
    })
    editApprovalDate.value = false
  } catch (error) {
    if (isValidationError(error)) {
      errors.value = error.response.data.errors
      return
    }
    throw error
  }
}
</script>

<template>
  <div class="overflow-x-auto">
    <div class="mb-6">
      <StepControls
        v-if="showStepControls"
        :step="step"
        @updated="handleUpdated"
      />
      <div class="flex justify-between text-lg font-bold pb-2 mb-2 border-b">
        <div class="flex space-x-2">
          <h2>
            {{ title }}
          </h2>
          <div v-if="dateApproved">
            <div v-if="!editApprovalDate" class="flex space-x-1">
              <div class="text-white bg-green-600 rounded-xl px-2">
                Approved: {{ dateApproved }}
              </div>
              <edit-icon-button class="text-black" @click="initEditApprovalDate" />
            </div>
            <div v-else class="flex space-x-1">
              <date-input v-model="newApprovalDate" />
              <button class="btn blue" @click="updateApprovalDate">Save</button>
              <RemoveButton @click="editApprovalDate = false" />
            </div>
          </div>
        </div>
        <div>
          <button class="btn btn-xs" @click="goToPrintable">Printable Application</button>
        </div>
      </div>

      <div class="screen-block-container">
        <slot name="sections">Step sections here!</slot>
      </div>
    </div>

    <div>
      <slot />
    </div>

    <slot name="log">
      <div class="mb-6 mt-4 border-t pt-4">
        <h3 class="mb-2">Step {{ step }} Progress Log</h3>
        <ApplicationLog :step="step" />
      </div>
    </slot>
  </div>
</template>
