<script>
import {mapGetters} from 'vuex'
import {isValidationError} from '@/http'
import {logEntries, deleteEntry} from '@/adapters/log_entry_repository'

export default {
    name: 'ConfirmDeleteLogEntry',
    props: {
        id: {
            required: true,
            type: String,
        }
    },
    data() {
        return {
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
        logEntry () {
            const logEntry = this.logEntries.find(entry => entry.id === this.id);
            return logEntry || {};
        },
        logDate () {
            return this.formatDate(this.logEntry.created_at)
        },
        flattenedErrors () {
            return Object.values(this.errors).flat();
        }
    },
    watch: {
        logEntry: {
            immediate: true,
            handler () {
                if (!this.logEntry.id) {
                    this.$router.go(-1);
                }
            }
        }
    },
    methods: {
        async deleteEntry()
        {
            try {
                // await this.$store.dispatch('applications/deleteLogEntry', {application: this.application, logEntry:  this.logEntry});
                await deleteEntry(`/api/groups/${this.group.uuid}/activity-logs`, this.id)
                this.$router.go(-1);
            } catch ( error ) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
                this.errors = {a: error.message};
                
            }
        }
    },
    setup () {
        return {
            logEntries
        }
    }
}
</script>
<template>
    <div>
        <h2>You are about to delete the following log entry:</h2>
        <div class="border-y py-2">
        <blockquote>
            <div v-html="logEntry.description"></div>
        </blockquote>
        <div class="ml-4 mt-2 mb-4 text-gray-700 text-sm" v-if="logEntry.causer">Logged by {{ logEntry.causer.name }}, {{ logDate }}</div>
        </div>

        <div>This can not be undone. Are you sure you want to continue?</div>

        <div v-if="flattenedErrors.length > 0" class="bg-red-200 text-red-900 rounded p-2 my-2">
            <ul>
                <li v-for="(err,idx) in flattenedErrors" :key="idx">
                    {{ err }}
                </li>
            </ul> 
        </div>
        
        <button-row @canceled="$router.go(-1)" @submitted="deleteEntry" submitText="Delete Log Entry"></button-row>
    </div>
</template>
<style lang="postcss" scope>
    blockquote {
        @apply mt-4 ml-4 border-l-4 pl-2 text-gray-700;
        font-size: 1.1rem;
    }
</style>