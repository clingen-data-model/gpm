<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: 'Submit for approval',
  },
  submissionName: {
    type: String,
    required: true,
  },
  notesLabel: {
    type: String,
    default: 'Required notes for reviewers:',
  },
  submitText: {
    type: String,
    default: 'Submit',
  },
  submitting: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'submitted', 'canceled']);

const notes = ref(null);
const errors = ref({});

watch(
  () => props.modelValue,
  (value) => {
    if (value) {
      notes.value = null;
      errors.value = {};
    }
  }
);

const confirmSubmission = () => {
  if (props.submitting) { return; }
  errors.value = {};

  if (!notes.value?.trim()) {
    errors.value = {
      notes: ['Notes are required.'],
    };
    return;
  }

  emit('submitted', notes.value);
};

const cancelSubmission = () => {
  notes.value = null;
  errors.value = {};
  emit('update:modelValue', false);
  emit('canceled');
};
</script>

<template>
  <teleport to="body">
    <transition name="fade">
      <modal-dialog
        :model-value="modelValue"
        :title="title"
        @update:model-value="emit('update:modelValue', $event)"
      >
        <p class="text-lg">
          You are about to submit your {{ submissionName }}.
        </p>

        <static-alert class="text-md" variant="info">
          Before submitting, please note:
          <ol class="list-decimal pl-6">
            <li>Typical response times are between one and two weeks.</li>
            <li>Questions, revisions, and other comments will be conveyed via email.</li>
            <li>Once submitted you will not be able to update this item until the submission has been processed.</li>
          </ol>
        </static-alert>

        <div class="mt-4 text-lg">
          {{ notesLabel }} <span class="text-red-600">*</span>
        </div>

        <input-row label="" :errors="errors.notes" vertical>
          <textarea v-model="notes" rows="5" class="w-full" :disabled="submitting" />
        </input-row>

        <button-row
          :submit-text="submitting ? 'Submitting...' : submitText"
          :disabled="submitting"
          @submitted="confirmSubmission"
          @canceled="cancelSubmission"
        />
      </modal-dialog>
    </transition>
  </teleport>
</template>