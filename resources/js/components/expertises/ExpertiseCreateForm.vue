<script setup>
    import {ref, watch} from 'vue';
    import {isValidationError} from '@/http';
    import { useExpertisesStore } from '@/stores/expertises';

    const props = defineProps({
        starterString: {
            type: String,
            required: false,
        }
    });
    const emits = defineEmits([
        'saved',
        'canceled'
    ]);
    const errors = ref({});
    const newExpertiseName = ref(null);

    watch(() => props.starterString, to => {
        newExpertiseName.value = to
    });

    const saveNewCredential = async () => {
        try {
            const newCredential = await useExpertisesStore().create(
                                        {name: newExpertiseName.value}
                                    )
                                    .then(rsp => rsp.data);

            newExpertiseName.value = null;
            emits('saved', newCredential);
        } catch (e) {
            if (isValidationError(e)) {
                errors.value = e.response.data.errors;
            }
        }
    };

    const cancelNewCredential = () => {
        newExpertiseName.value = null;
        emits('canceled');
    };
</script>

<template>
  <div>
    <input-row v-model="newExpertiseName" label="Name" :errors="errors.name" />
    <button-row submit-text="Create Credential" @submitted="saveNewCredential" @cancel="cancelNewCredential" />
  </div>
</template>
