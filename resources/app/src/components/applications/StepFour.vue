<template>
    <base-step 
        :step="4"
        :document-type="5"
        document-name="Final Approved Application"
        :document-gets-reviewed="true"
        approve-button-label="Approve Final Application"
        title="Final Approval"
    >
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from './BaseStep'

export default {
    name: 'StepFour',
    components: {
        BaseStep,
    },
    props: {
        
    },
    data() {
        return {
            
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    methods: {

    }
}
</script>