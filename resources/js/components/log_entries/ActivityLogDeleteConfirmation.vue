<script>
import {ref, computed} from 'vue';
import { formatDate } from '@/date_utils';
import is_validation_error from '@/http/is_validation_error';
import { fetchEntries, deleteEntry } from '@/adapters/log_entry_repository'


export default {
    name: 'ConfirmDeleteLogEntry',
    props: {
        logEntry: {
            required: true,
            type: Object,
        },
        apiUrl: {
            required: true,
            type: String
        }
    },
    emits: [
        'deleted',
        'canceled'
    ],
    setup (props, context) {
        const errors = ref({});
        const logDate = computed(() => {
            return formatDate(props.logEntry.log_date)
        });
        const flattenedErrors = computed(() => {
            return Object.values(errors.value).flat();
        });

        const deleteLogEntry = async () => {
            try {
                await deleteEntry(props.apiUrl, props.logEntry.id);
                context.emit('deleted')
                await fetchEntries(props.apiUrl);
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors;
                    return;
                }
                errors.value = {a: error.message};
            }
        }


        return {
            errors,
            logDate,
            flattenedErrors,
            deleteLogEntry
        }
    }
}
</script>
<template>
  <div>
    <p>You are about to delete the following log entry:</p>

    <div class="border-y py-2">
      <blockquote>
        <div v-html="logEntry.description" />
      </blockquote>
      <div v-if="logEntry.causer" class="ml-4 mt-2 mb-4 text-gray-700 text-sm">
        Logged by {{ logEntry.causer.name }}, {{ logDate }}
      </div>
    </div>

    <div>This can not be undone. Are you sure you want to continue?</div>

    <div v-if="flattenedErrors.length > 0" class="bg-red-200 text-red-900 rounded p-2 my-2">
      <ul>
        <li v-for="(err,idx) in flattenedErrors" :key="idx">
          {{ err }}
        </li>
      </ul> 
    </div>
        
    <button-row submitText="Delete Log Entry" @canceled="$emit('canceled')" @submitted="deleteLogEntry" />
  </div>
</template>
<style lang="postcss" scope>
    blockquote {
        @apply mt-4 ml-4 border-l-4 pl-2 text-gray-700;
        font-size: 1.1rem;
    }
</style>