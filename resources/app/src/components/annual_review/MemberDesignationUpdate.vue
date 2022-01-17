<template>
    <div>
        <member-designation-form
            v-model="workingCopy"
            :errors="errors"
            @updated="$emit('updated')"
        />
        <hr>
        <input-row vertical 
            label="Does this represent a change from previous years?"
            :errors="errors.member_designation_changed"
            type="radio-group"
            :options="[
                {value: 'yes', label: 'Yes'},
                {value: 'no', label: 'No'}
            ]"
        >
        </input-row>
    </div>
</template>
<script>
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm'
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'MemberDesignationUpdate',
    components: {
        MemberDesignationForm
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits, 'updated' ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>