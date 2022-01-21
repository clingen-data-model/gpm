<template>
    <application-section title="">
            <input-row :errors="errors.website_attestation" vertical>
                <template v-slot:label>
                    <p>
                        Please review your ClinGen EP webpage, including Expert Panel Status, description, membership, COI and relevant documentation, including publications. See the <a href="https://docs.google.com/document/d/1GeyR1CBqlzLHOdlPLJt0uA29Z-2ysmTX1dtH9PDmqRo/edit?usp=sharing">Coordinator Resource Document</a> for instructions on how to update web pages.
                    </p>
                </template>
                <checkbox 
                    label="I attest that the information on the webpage is up-to-date and accurate." 
                    v-model="workingCopy.data.website_attestation"
                    :disabled="isComplete"
                />
            </input-row>
    </application-section>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'

export default {
    name: 'WebsiteAttestation',
    components: {
        ApplicationSection
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [
        ...mirror.emits
    ],
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