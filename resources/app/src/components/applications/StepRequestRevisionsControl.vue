<script setup>
    import {ref, useAttrs} from 'vue'

    import RejectStepForm from '@/components/applications/RejectStepForm.vue'

    const attrs = useAttrs();
    // eslint-disable-next-line
    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })
    const emits = defineEmits(['revisionsRequested'])

    const showRejectForm = ref(false);
    const startRejectSubmission = () => {
        showRejectForm.value = true;
    };
    const handleRejected = () => {
        hideRejectForm();
        emits('revisionsRequested');
    };
    const hideRejectForm = () => {
        showRejectForm.value = false;
    };

</script>
<template>
    <div>
        <button
            v-if="group.expert_panel.hasPendingSubmissionForCurrentStep"
            class="btn btn-lg w-full"
            @click="startRejectSubmission"
            v-bind="attrs"
        >
            Request revisions
        </button>
        <teleport to="body">
            <modal-dialog
                title="Request Revisions to Application"
                v-model="showRejectForm"
                size="xl"
                @closed="$refs.rejectsubmissionform.clearForm()"
            >
                <reject-step-form
                    ref="rejectsubmissionform"
                    @saved="handleRejected"
                    @canceled="hideRejectForm"
                />
            </modal-dialog>
        </teleport>
    </div>
</template>
