<template>
    <div>
        <input-row :errors="errors.goals" vertical>
            <template v-slot:label>
                Describe the Expert Panel’s plans and goals for the next year, for example, finishing curations, working on manuscript, etc.
                <div v-if="group.isVcep()">
                    Please include:
                    <ul class="list-decimal pl-8">
                        <li>Progress on resolving discrepancies between existing ClinVar submitters in addition to noting other priorities.</li>
                        <li>When the Expert Panel anticipates submitting for approval of any remaining steps (i.e. Step 2 and Step 3).</li>
                    </ul>
                </div>
            </template>
            <textarea v-model="workingCopy.goals" rows="5" class="w-full"></textarea>
        </input-row>

        <input-row 
            label="Do the co-chairs plan to continue leading the EP for the next year?"
            :errors="errors.cochair_commitment"
            vertical
        >
            <div class="ml-4">
                <radio-button v-model="workingCopy.cochair_commitment" value="yes">Yes</radio-button>
                <radio-button v-model="workingCopy.cochair_commitment" value="no">No</radio-button>
                <transition name="slide-fade-down">                                
                    <input-row 
                        label="Please explain" 
                        :errors="workingCopy.cochair_commitment_details" 
                        vertical
                        class="ml-4"
                        v-if="workingCopy.cochair_commitment == 'no'"
                    >
                        <textarea rows="5" v-model="workingCopy.cochair_commitment_details" class="w-full"></textarea>
                    </input-row>
                </transition>
            </div>
        </input-row>
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
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
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