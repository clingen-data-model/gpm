<script setup>
    import ApproveStepForm from '@/components/applications/ApproveStepForm.vue'
    import { computed, ref, useAttrs } from 'vue';

    const props = defineProps({
        group: {
            type: Object,
            required: true
        },
        step: {
            type: Number,
            required: true
        }
    });
    const emits = defineEmits(['stepApproved']);
    const attrs = useAttrs();
    const showApproveForm = ref(false);
    const isCurrentStep = computed(() => props.step === props.group.expert_panel.current_step)
    const buttonTitle = computed(() => isCurrentStep.value
                                            ? 'Approve this step'
                                            : 'You can only approve the application\'s current step')

    const startApproveStep = () => {
        showApproveForm.value = true;
    };

    const hideApproveForm = () => {
        showApproveForm.value = false;
    };

    const handleApproved = () => {
            hideApproveForm();
            emits('stepApproved');
    };

</script>

<template>
    <div>
        <button
            :disabled="!isCurrentStep"
            :title="buttonTitle"
            class="btn btn-lg w-full"
            v-bind="attrs"
            @click="startApproveStep"
        >
            <slot></slot>
        </button>

        <teleport to="body">
            <modal-dialog v-model="showApproveForm" size="xl" @closed="$refs.approvestepform.clearForm()">
                <ApproveStepForm
                    ref="approvestepform"
                    @saved="handleApproved"
                    @canceled="hideApproveForm"
                />
            </modal-dialog>
        </teleport>
    </div>
</template>
