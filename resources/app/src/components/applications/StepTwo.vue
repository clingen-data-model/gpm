<template>
    <base-step 
        :step="2"
        :document-type="2"
        document-name="Draft Specifications"
        :document-gets-reviewed="false"
        approve-button-label="Approve Draft Specifications"
        title="Draft ACMG Guideline Specifications"
    >
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from '@/components/applications/BaseStep'

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
            application: 'applications/currentItem'
        })
    },
    methods: {

    }
}
</script>