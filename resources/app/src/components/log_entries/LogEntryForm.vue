<template>
    <form-container class="log-entry-form" ref="form-container">
        <h2 class="block-title">{{this.$route.meta.title}}</h2>
        <input-row label="Log Date" v-model="newEntry.log_date" :errors="errors.log_date" type="date" ref="logdate"></input-row>
        <step-input v-model="newEntry.step" :errors="errors.step" v-if="application.expert_panel_type_id == 2"></step-input>
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
import {mapGetters} from 'vuex'
import { formatDate } from '@/date_utils'
import StepInput from '@/components/forms/StepInput'
import RichTextEditor from '@/components/forms/RichTextEditor'


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
            application: 'applications/currentItem'
        })
    },
    watch: {
        id: {
            immediate: true,
            handler: function() {
                const entry = this.findEntry();
                if (entry) {
                    this.syncEntry(entry)
                }
            }
        },
        application: {
            immediate: true,
            handler: function () {
                const entry = this.findEntry();
                if (entry) {
                    this.syncEntry(entry);
                }
            }
        }
    },
    methods: {
        findEntry () {
            if (this.id === null) {
                return null;
            }
            if (this.application.log_entries) {
                return this.application.log_entries.find(i => i.id == this.id);
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
                step: entry.properties.step,
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
                    await this.$store.dispatch(
                        'applications/updateLogEntry', 
                        {
                            application: this.application, 
                            updatedEntry: this.newEntry,
                        }
                    );
                } else {
                    await this.$store.dispatch('applications/addLogEntry', {application: this.application, logEntryData: this.newEntry})
                }
                this.initNewEntry();
                this.$emit('saved');
            } catch (error) {
                if (error.response && error.response.status == 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                    return;
                }
            }
        },
    },
    mounted() {
        this.$el.querySelectorAll('input')[0].focus();
    }
}
</script>