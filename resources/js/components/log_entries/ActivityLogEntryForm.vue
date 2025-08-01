<script setup>
    import { ref, watch, computed } from 'vue'
    import {useStore} from 'vuex'
    import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
    import StepInput from '@/components/forms/StepInput.vue'
    import {saveEntry, updateEntry, fetchEntries} from '@/adapters/log_entry_repository'

    const props = defineProps({
        logEntry: {
                required: false,
                default: () => {
                    return {
                        log_date: new Date().toISOString(),
                        step: null,
                        entry: '',
                    }
                }
            },
            apiUrl: {
                required: true,
                type: String
            }
    });
    const emits = defineEmits(['saved', 'canceled'])
    const store = useStore();

    // DATA
    const newEntry = ref({
        log_date: new Date().toISOString(),
        step: null,
        entry: '',
    });
    const errors = ref({});

    //COMPUTED
    const group = computed(() => store.getters['groups/currentItemOrNew']);

    // METHODS
    const syncEntry =  (entry) => {
        if (!entry || !entry.created_at) {
            return;
        }

        newEntry.value = {
            id: entry.id,
            log_date: new Date(Date.parse((entry.created_at))).toISOString(),
            step: entry.step,
            entry: entry.description
        }
    }

    const initNewEntry = () => {
        newEntry.value = {
            log_date: new Date().toISOString(),
            step: null,
            entry: ''
        }
    }

    const save = async () => {
        try {
            if (newEntry.value.id) {
                await updateEntry(`${props.apiUrl}/${newEntry.value.id}`, newEntry.value);
            } else {
                await saveEntry(props.apiUrl, newEntry.value);
            }

            await fetchEntries(props.apiUrl);
            initNewEntry();
            emits('saved', newEntry.value);
        } catch (error) {
            if (error.response && Number.parseInt(error.response.status) === 422 && error.response.data.errors) {
                errors.value = error.response.data.errors
            }
        }
    };

    const cancel = () => {
        initNewEntry();
        emits('canceled');
    }

    watch(
        () => props.logEntry,
        (to) => {
            syncEntry(to)
        },
        {immediate: true}
    );

</script>
<template>
  <form-container class="log-entry-form">
    <input-row
      v-model="newEntry.log_date"
      label="Log Date"
      :errors="errors.log_date"
      type="date"
    />
    <StepInput v-if="group.is_vcep" v-model="newEntry.step" :errors="errors.step" />
    <input-row label="Entry" :errors="errors.entry">
      <RichTextEditor v-model="newEntry.entry" />
    </input-row>
    <button-row>
      <button class="btn" @click="cancel">
        Cancel
      </button>
      <button class="btn blue" @click="save">
        Save
      </button>
    </button-row>
  </form-container>
</template>
