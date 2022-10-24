<template>
    <div>
        <input-row
            v-model="workingCopy.data.goals"
            type="large-text"
            :errors="errors.goals"
            vertical
            :disabled="isComplete"
        >
            <template v-slot:label>
                Describe the Expert Panel’s plans and goals for the next year, for example, finishing curations, working on manuscript, etc.
                <div v-if="workingCopy.is_vcep">
                    Please include:
                    <ul class="list-decimal pl-8">
                        <li>Progress on resolving discrepancies between existing ClinVar submitters in addition to noting other priorities.</li>
                        <li>When the Expert Panel anticipates submitting for approval of any remaining steps (i.e. Step 2 and Step 3).</li>
                    </ul>
                </div>
            </template>
        </input-row>

        <input-row
            label="Do the co-chairs plan to continue leading the EP for the next year?"
            v-model="workingCopy.data.cochair_commitment"
            type="radio-group"
            :errors="errors.cochair_commitment"
            :options="[{value: 'yes'}, {value: 'no'}]"
            vertical
            :disabled="isComplete"
        />
        <transition name="slide-fade-down">
            <input-row
                label="Please explain"
                v-model="workingCopy.data.cochair_commitment_details"
                type="large-text"
                :errors="errors.cochair_commitment_details"
                vertical
                class="ml-4"
                v-if="workingCopy.data.cochair_commitment == 'no'"
                :disabled="isComplete"
            />
        </transition>
    </div>
</template>

<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'goalsForm',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits ],
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
    },
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>
