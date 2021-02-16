<template>
    <base-step 
        :step="2"
        :document-type="2"
        document-name="Draft Specifications"
        :document-gets-reviewed="false"
        approve-button-label="Approve Draft Specifications"
    >
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from './BaseStep'

export default {
    name: 'StepTwo',
    components: {
        BaseStep
    },
    props: {
        
    },
    data() {
        return {
            
        }
    },
    computed: {
        ...mapGetters({
            application: 'currentItem'
        })
    },
    methods: {

    }
}
</script>