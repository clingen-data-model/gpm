<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import mirror from '@/composables/setup_working_mirror'

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
<template>
    <ApplicationSection title="Webpage Updates">
            <input-row :errors="errors.website_attestation" vertical>
                <template #label>
                    <p>
                        Please review your ClinGen EP webpage, including description, membership, and relevant documentation, including publications. See the <coordinator-resource-link /> for instructions on how to update web pages.
                    </p>
                </template>
                <checkbox 
                    label="I attest that the information on the webpage is up-to-date and accurate." 
                    v-model="workingCopy.data.website_attestation"
                    :disabled="isComplete"
                />
            </input-row>
    </ApplicationSection>
</template>