<script setup>
    import {ref, watch} from 'vue';
    import {useStore} from 'vuex';
    import {isValidationError} from '@/http';

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
    const store = useStore();
    const errors = ref({});
    const newCredentialName = ref(null);

    watch(() => props.starterString, to => {
        newCredentialName.value = to
    });

    const saveNewCredential = async () => {
        try {
            const newCredential = await store.dispatch(
                                        'credentials/create',
                                        {name: newCredentialName.value}
                                    )
                                    .then(rsp => rsp.data);

            newCredentialName.value = null;
            emits('saved', newCredential);
        } catch (e) {
            if (isValidationError(e)) {
                errors.value = e.response.data.errors;
            }
        }
    };

    const cancelNewCredential = () => {
        newCredentialName.value = null;
        emits('canceled');
    };
</script>

<template>
  <div>
    <input-row v-model="newCredentialName" label="Name" :errors="errors.name" />
    <button-row submit-text="Create Credential" @submitted="saveNewCredential" @cancel="cancelNewCredential" />
  </div>
</template>
