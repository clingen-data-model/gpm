<template>
                <app-section title="Membership">
                    <p>
                        Please list the entire membership of the Expert Panel.
                    </p>
                    <p v-if="workingCopy.is_vcep">
                        Note: If changes are made to an Expert Panel Co-chair(s) or coordinator, please report them directly to the <a href="mailto:cdwg_oversightcommittee@clinicalgenome.org">Clinical Domain Working Group Oversight Committee</a> when they occur. All current EP members must complete a Conflict of Interest (COI) survey each year. If all members of your EP have filled out the GPM generated COI survey, some of the information will be auto populated.
                    </p>

                    <member-list
                        :readonly="isComplete"
                    />

                    <input-row
                        vertical
                        label="Please attest that your membership is up to date"
                        :errors="errors.membership_attestation"
                        type="radio-group"
                        v-model="workingCopy.data.membership_attestation"
                        :options="[
                            {value: 'I have reviewed and made the appropriate updates to membership as needed.'},
                            {value: 'I have reviewed and there are no changes needed.'}
                        ]"
                        :disabled="isComplete"
                    />

                    <input-row
                        vertical
                        label="Has the Expert Panel chair changed over the last year?"
                        :errors="errors.expert_panels_change"
                        type="radio-group"
                        v-model="workingCopy.data.expert_panels_change"
                        :options="[
                            {value: 'Yes'},
                            {value: 'No'}
                        ]"
                        :disabled="isComplete"
                    />
                    
                </app-section>

</template>
<script>
import mirror from '@/composables/setup_working_mirror'
import AppSection from '@/components/expert_panels/ApplicationSection.vue'
import MemberList from '@/components/groups/MemberList.vue'

export default {
    name: 'MembershipUpdate',
    components: {
        AppSection,
        MemberList,
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
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
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
