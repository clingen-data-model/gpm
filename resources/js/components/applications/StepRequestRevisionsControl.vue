<script setup>
import { nextTick, ref, useAttrs } from 'vue'

import RejectStepForm from '@/components/applications/RejectStepForm.vue'

defineProps({
  group: {
    type: Object,
    required: true,
  },
  submission: {
    type: Object,
    required: true,
  },
})
const emit = defineEmits(['revisionsRequested'])
const attrs = useAttrs()

const rejectSubmissionForm = ref(null)
const showRejectForm = ref(false)

const startRejectSubmission = async () => {
  showRejectForm.value = true
  await nextTick()
  rejectSubmissionForm.value?.getEmailTemplate()
}

const hideRejectForm = () => {
  showRejectForm.value = false
}

const handleRejected = () => {
  hideRejectForm()
  emit('revisionsRequested')
}

const handleClosed = () => {
  rejectSubmissionForm.value?.clearForm()
}
</script>
<template>
  <div>
    <button
      class="btn btn-lg w-full"
      v-bind="attrs"
      @click="startRejectSubmission"
    >
      Request revisions
    </button>
    <teleport to="body">
      <modal-dialog
        v-model="showRejectForm"
        title="Request Revisions to Application"
        size="xl"
        @closed="handleClosed"
      >
        <RejectStepForm
          ref="rejectSubmissionForm"
          :group="group"
          :submission="submission"
          @saved="handleRejected"
          @canceled="hideRejectForm"
        />
      </modal-dialog>
    </teleport>
  </div>
</template>
