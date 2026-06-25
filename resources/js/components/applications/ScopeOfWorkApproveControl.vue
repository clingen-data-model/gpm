<script setup>
import { ref, useAttrs } from 'vue'
import ScopeOfWorkApproveForm from './ScopeOfWorkApproveForm.vue'

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

const emit = defineEmits([
  'approved',
])

const attrs = useAttrs()
const showApproveForm = ref(false)
const approveForm = ref(null)

const startApproval = () => {
  showApproveForm.value = true
}

const hideApprovalForm = () => {
  showApproveForm.value = false
}

const handleApproved = () => {
  hideApprovalForm()
  emit('approved')
}

const handleClosed = () => {
  approveForm.value?.clearForm()
}
</script>

<template>
  <div>
    <button
      class="btn btn-lg w-full"
      v-bind="attrs"
      @click="startApproval"
    >
      Approve Scope of Work Revision
    </button>

    <teleport to="body">
      <modal-dialog
        v-model="showApproveForm"
        title="Approve Scope of Work Revision"
        size="xl"
        @closed="handleClosed"
      >
        <ScopeOfWorkApproveForm
          ref="approveForm"
          :group="group"
          :submission="submission"
          @saved="handleApproved"
          @canceled="hideApprovalForm"
        />
      </modal-dialog>
    </teleport>
  </div>
</template>