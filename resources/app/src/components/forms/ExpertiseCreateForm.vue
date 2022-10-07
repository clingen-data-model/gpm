<script setup>
    import {ref, watch} from 'vue'
    import {api, isValidationError} from '@/http'

    const props = defineProps({
        starterString: {
            type: String,
            required: false,
        }
    })

    const emits = defineEmits([
        'saved',
        'canceled'
    ]);

    const errors = ref({})
    const newExpertiseName = ref(null);

    watch(() => props.starterString, to => {
        newExpertiseName.value = to
    });

    const saveNewExpertise = async () => {
        try {
            const newExpertise = await api.post('/api/expertises', {name: newExpertiseName.value})
                                    .then(rsp => rsp.data);
            newExpertiseName.value = null;
            emits('saved', newExpertise);
        } catch (e) {
            if (isValidationError(e)) {
                errors.value = e.response.data.errors;
            }
        }
    }
    const cancelNewExpertise = () => {
        newExpertiseName.value = null;
        emits('canceled');
    }
</script>

<template>
    <div>
        <input-row label="Name" v-model="newExpertiseName" :errors="errors.name" />
        <button-row @submitted="saveNewExpertise" @cancel="cancelNewExpertise" submit-text="Create Expertise" />
    </div>
</template>
