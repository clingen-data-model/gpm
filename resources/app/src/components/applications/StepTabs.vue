<template>
    <div>
        <div v-if="application.isGcep">
            <step-one @stepApproved="handleApproved"></step-one>
        </div>
            <tabs-container 
                tab-location="right" 
                v-model="activeIndex" 
                v-if="this.application.expert_panel_type_id == 2"
            >
            <tab-item label="Group Definition">
                <step-one @stepApproved="handleApproved"></step-one>
            </tab-item>
            <tab-item label="Draft Specifications">
                <step-two @stepApproved="handleApproved"></step-two>
            </tab-item>
            <tab-item label="Pilot Specfications">
                <step-three @stepApproved="handleApproved"></step-three>
            </tab-item>
            <tab-item label="Sustained Curation">
                <step-four @stepApproved="handleApproved"></step-four>
            </tab-item>
        </tabs-container>

    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import StepOne from '@/components/applications/StepOne'
import StepTwo from '@/components/applications/StepTwo'
import StepThree from '@/components/applications/StepThree'
import StepFour from '@/components/applications/StepFour'

export default {
    components: {
        StepOne,
        StepTwo,
        StepThree,
        StepFour,
    },
    emits: ['stepApproved'],
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
            this.$emit('stepApproved')
        }
    }
    // watch: {
    //     application: {
    //         deep: true,
    //         handler: function () {
    //             this.activeStep = this.application.current_step
    //         }
    //     }
    // }
}
</script>