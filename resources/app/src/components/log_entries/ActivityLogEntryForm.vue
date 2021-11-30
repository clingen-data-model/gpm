<template>
    <form-container class="log-entry-form" ref="form-container">
        <hr>
        <input-row label="Log Date" 
            v-model="newEntry.log_date" 
            :errors="errors.log_date" 
            type="date" 
            ref="logdate"
        ></input-row>
        <!-- <step-input v-model="newEntry.step" :errors="errors.step" v-if="application.expert_panel_type_id == 2"></step-input> -->
        <input-row label="Entry" :errors="errors.entry">
            <rich-text-editor v-model="newEntry.entry"></rich-text-editor>
        </input-row>
        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </button-row>
    </form-container>
</template>
<script>
import { ref, watch } from 'vue'
// import StepInput from '@/components/forms/StepInput'
import RichTextEditor from '@/components/forms/RichTextEditor'
import {saveEntry, updateEntry, fetchEntries} from '@/adapters/log_entry_repository'


export default {
    name: 'LogEntryForm',
    components: {
        // StepInput,
        RichTextEditor
    },
    props: {
        logEntry: {
            required: false,
            default: () => {
                return {
                    log_date: new Date(),
                    step: null,
                    entry: '',
                }
            }
        },
        apiUrl: {
            required: true,
            type: String
        }
    },
    setup (props, context) {
        const newEntry = ref({
            log_date: new Date(),
            step: null,
            entry: '',
        });
        const errors = ref({});

        const syncEntry =  (entry) => {
            if (!entry || !entry.created_at) {
                return;
            }

            newEntry.value = {
                id: entry.id,
                log_date: new Date(Date.parse((entry.created_at))),
                step: entry.properties ? entry.properties.step : null,
                entry: entry.description
            }
        }

        const initNewEntry = () => {
            newEntry.value = {
                log_date: new Date(),
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
                context.emit('saved', newEntry.value);
            } catch (error) {
                if (error.response && error.response.status == 422 && error.response.data.errors) {
                    errors.value = error.response.data.errors
                    return;
                }
            }
        };

        const cancel = () => {
            initNewEntry();
            context.emit('canceled');
        }

        watch(
            () => props.logEntry, 
            (to) => {
                syncEntry(to)
            },
            {immediate: true}
        );

        return {
            newEntry,
            errors,
            syncEntry,
            initNewEntry,
            save,
            cancel,
        }
    },
    mounted() {
        // this.$el.querySelectorAll('input')[0].focus();
    }
}
</script>