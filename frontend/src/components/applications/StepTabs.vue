<template>
    <div>
        <div v-if="this.application.ep_type_id == 1">
            <step-one></step-one>
        </div>
        
        <tabs-container tab-location="right" v-model="activeIndex" v-if="this.application.ep_type_id == 2">
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
import StepOne from './StepOne'
import StepTwo from './StepTwo'
import StepThree from './StepThree'
import StepFour from './StepFour'

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
            application: 'currentItem'
        }),
        activeIndex: {
            deep: true,
            get() {
                console.info('activeIndex: ', this.application)
                return (this.application && this.application.current_step) ? this.application.current_step - 1 : 0
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