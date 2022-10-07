<template>
    <div>
        <div v-if="application.isGcep">
            <step-one @stepApproved="handleApproved" @updated="handleUpdated"></step-one>
        </div>
            <tabs-container
                tab-location="right"
                v-model="activeIndex"
                v-if="this.application.expert_panel_type_id == 2"
            >
            <tab-item label="Group Definition">
                <step-one @stepApproved="handleApproved" @updated="handleUpdated"></step-one>
            </tab-item>
            <tab-item label="Draft Specifications">
                <step-two @stepApproved="handleApproved" @updated="handleUpdated"></step-two>
            </tab-item>
            <tab-item label="Pilot Specfications">
                <step-three @stepApproved="handleApproved" @updated="handleUpdated"></step-three>
            </tab-item>
            <tab-item label="Sustained Curation">
                <step-four @stepApproved="handleApproved" @updated="handleUpdated"></step-four>
            </tab-item>
        </tabs-container>

    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import StepOne from '@/components/applications/StepOne.vue'
import StepTwo from '@/components/applications/StepTwo.vue'
import StepThree from '@/components/applications/StepThree.vue'
import StepFour from '@/components/applications/StepFour.vue'

export default {
    components: {
        StepOne,
        StepTwo,
        StepThree,
        StepFour,
    },
    emits: ['approved', 'updated'],
    data() {
        return {
            activeStep: 1
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
        activeIndex: {
            deep: true,
            get() {
                return (this.application && this.application.current_step) ? this.application.current_step - 1 : 0
            },
            set(value) {
                this.activeStep = value+1
            }

        }
    },
    methods: {
        handleApproved () {
            this.$emit('approved');
            this.$emit('updated');
        },
        handleUpdated () {
            this.$emit('updated')
        }
    }
}
</script>
