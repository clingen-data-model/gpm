<template>
    <div>
        <div v-if="application.isGcep">
            <step-one></step-one>
        </div>
        
            <tabs-container tab-location="right" v-model="activeIndex" v-if="this.application.expert_panel_type_id == 2">
            <tab-item label="Step 1 - Define">
                <step-one></step-one>
            </tab-item>
            <tab-item label="Step 2 - Draft Rules">
                <step-two></step-two>
            </tab-item>
            <tab-item label="Step 3 - Pilot Rules">
                <step-three></step-three>
            </tab-item>
            <tab-item label="Step 4 - Final Approval">
                <step-four></step-four>
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