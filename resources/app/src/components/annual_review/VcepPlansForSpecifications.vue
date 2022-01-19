<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VcepPlansForSpecifications',
    components: {
        ApplicationSection,
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
    <application-section title="Plans for rule specification of additional genes">
        <input-row
            v-model="workingCopy.specification_plans"
            :errors="errors.specification_plans"
            label="Are you planning to start the rule specification process for  anew gene in the coming year?"
            type="radio-group"
            :options="[{value: 'yes', label: 'Yes'}, {value: 'no', label: 'No'}]"
            vertical
        />
        <input-row
            v-if="workingCopy.specification_plans == 'yes'"
            v-model="workingCopy.specification_plans_details" 
            :errors="errors.specification_plans_details" 
            type="large-text" 
            label="What are your plans?" 
            vertical
            class="ml-4"
        />
    </application-section>
</template>
