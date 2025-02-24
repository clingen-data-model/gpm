<template>
    <div>
        <div v-if="application.is_gcep">
            <StepOne @stepApproved="handleApproved" @updated="handleUpdated"></StepOne>
        </div>
            <tabs-container
                tab-location="right"
                v-model="activeIndex"
                v-if="application.expert_panel_type_id == 2"
            >
            <tab-item label="Group Definition">
                <StepOne @stepApproved="handleApproved" @updated="handleUpdated"></StepOne>
            </tab-item>
            <tab-item label="Draft Specifications">
                <StepTwo @stepApproved="handleApproved" @updated="handleUpdated"></StepTwo>
            </tab-item>
            <tab-item label="Pilot Specfications">
                <StepThree @stepApproved="handleApproved" @updated="handleUpdated"></StepThree>
            </tab-item>
            <tab-item label="Sustained Curation">
                <StepFour @stepApproved="handleApproved" @updated="handleUpdated"></StepFour>
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
