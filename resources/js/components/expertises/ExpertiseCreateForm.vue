<script setup>
    import {isValidationError} from '@/http';
    import {ref, watch} from 'vue';
    import {useStore} from 'vuex';

    const store = useStore();
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
            const newCredential = await store.dispatch(
                                        'expertises/create',
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
        <input-row label="Name" v-model="newExpertiseName" :errors="errors.name" />
        <button-row @submitted="saveNewCredential" @cancel="cancelNewCredential" submit-text="Create Credential" />
    </div>
</template>
