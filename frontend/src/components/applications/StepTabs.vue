<template>
    <div>
        <tabs-container tab-location="right">
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
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>