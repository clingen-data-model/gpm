<script setup>
    import { ref, computed, useAttrs } from 'vue';
    import ApproveStepForm from '@/components/applications/ApproveStepForm.vue'

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
    const isCurrentStep = computed(() => props.step === Number.parseInt(props.group.expert_panel.current_step))
    const buttonTitle = computed(() => {
							if (isAffiliationMissing.value) {
								return 'Cannot approve: Affiliation ID is missing';
							}
							return isCurrentStep.value
								? 'Approve this step'
								: 'You can only approve the application\'s current step';
						});

	const isAffiliationMissing = computed(() => {
		if (props.step !== 1) return false;
		const id = props.group?.expert_panel?.affiliation_id;
		return !id || !id.toString().trim();
	});

    const startApproveStep = () => {
      	if (isAffiliationMissing.value) {
			alert('Cannot approve: Affiliation ID is missing.');
			return;
		}

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
			:disabled="!isCurrentStep || isAffiliationMissing"
			:title="buttonTitle"
			class="btn btn-lg w-full"
			v-bind="attrs"
			@click="startApproveStep"
			>
			<slot />
		</button>
		<p v-if="isAffiliationMissing && props.step === 1" class="text-red-600 text-sm mt-1">
			You must enter an Affiliation ID before approving this step.
		</p>

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
