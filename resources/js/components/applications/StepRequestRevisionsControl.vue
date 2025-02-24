<script setup>
    import RejectStepForm from '@/components/applications/RejectStepForm.vue'

    import {ref, useAttrs} from 'vue'

    // eslint-disable-next-line unused-imports/no-unused-vars
    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })
    const emits = defineEmits(['revisionsRequested'])
    const attrs = useAttrs();
    const rejectsubmissionform = ref(null);
    const showRejectForm = ref(false);
    const startRejectSubmission = () => {
        showRejectForm.value = true;
        rejectsubmissionform.value.getEmailTemplate()
    };
    const hideRejectForm = () => {
        showRejectForm.value = false;
    };
    const handleRejected = () => {
        hideRejectForm();
        emits('revisionsRequested');
    };

</script>
<template>
  <div>
    <button
      v-if="group.expert_panel.hasPendingSubmissionForCurrentStep"
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
        @closed="$refs.rejectsubmissionform.clearForm()"
      >
        <RejectStepForm
          ref="rejectsubmissionform"
          @saved="handleRejected"
          @canceled="hideRejectForm"
        />
      </modal-dialog>
    </teleport>
  </div>
</template>
