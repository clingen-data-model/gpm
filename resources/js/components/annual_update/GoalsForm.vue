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
            label="Do you anticipate changes to your expert panel leadership (e.g. chair, coordinator, core approval member), in the next year?"
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
                v-if="workingCopy.data.cochair_commitment == 'yes'"
                :disabled="isComplete"
            />
        </transition>

        <input-row
            v-if="version < 2024"
            label="Please list any chairs that have led the EP for 3 or more years:"
            vertical
            :errors="errors.long_term_chairs"
        >
            <person-search-select
                v-model="workingCopy.data.long_term_chairs"
                :allow-add="false"
                :disabled="isComplete"
                :multiple="true"
            />
        </input-row>
    </div>
</template>

<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'GoalsForm',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
        version: {
            type: Number,
            required: false,
            default: 0,
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
