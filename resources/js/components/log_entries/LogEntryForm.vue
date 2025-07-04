<script>
import {mapGetters} from 'vuex'
import { formatDate } from '@/date_utils'
import StepInput from '@/components/forms/StepInput.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import {logEntries, saveEntry, updateEntry} from '@/adapters/log_entry_repository'

export default {
    name: 'LogEntryForm',
    components: {
        StepInput,
        RichTextEditor
    },
    props: {
        id: {
            required: false,
            default: null
        }
    },
    emits: [
        'canceled',
        'saved',
    ],
    setup () {
        return {
            logEntries
        }
    },
    data() {
        return {
            newEntry: {
                log_date: new Date(),
                step: null,
                entry: ''
            },
            errors: {},
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        }
    },
    watch: {
        id: {
            immediate: true,
            handler() {
                const entry = this.findEntry();
                if (entry) {
                    this.syncEntry(entry)
                }
            }
        },
        application: {
            immediate: true,
            handler () {
                const entry = this.findEntry();
                if (entry) {
                    this.syncEntry(entry);
                }
            }
        }
    },
    mounted() {
        this.$el.querySelectorAll('input')[0].focus();
    },
    methods: {
        findEntry () {
            if (this.id === null) {
                return null;
            }
            if (this.logEntries) {
                return this.logEntries.find(i => i.id === this.id);
            }
        },
        initNewEntry () {
            this.newEntry = {
                log_date: formatDate(new Date),
                step: null,
                entry: ''
            }
        },
        syncEntry (entry) {
            if (!entry) {
                return;
            }
            this.newEntry = {
                id: entry.id,
                log_date: formatDate(new Date(Date.parse((entry.created_at)))),
                step: Number.parseInt(entry.step),
                entry: entry.description
            }
        },
        cancel() {
            this.initNewEntry();
            this.$emit('canceled');
        },
        async save() {
            try {
                if (this.newEntry.id) {
                    updateEntry(`/api/groups/${this.group.uuid}/activity-logs/${this.newEntry.id}`, this.newEntry);
                } else {
                    saveEntry(`/api/groups/${this.group.uuid}/activity-logs`, this.newEntry)
                }
                this.initNewEntry();
                this.$emit('saved');
            } catch (error) {
                if (error.response && Number.parseInt(error.response.status) === 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                }
            }
        },
    },
}
</script>
<template>
  <form-container class="log-entry-form">
    <input-row v-model="newEntry.log_date" label="Log Date" :errors="errors.log_date" type="date" />
    <StepInput v-if="application.expert_panel_type_id == 2" v-model="newEntry.step" :errors="errors.step" />
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
