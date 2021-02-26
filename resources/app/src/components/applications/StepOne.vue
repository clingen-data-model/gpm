<template>
    <base-step 
        :step="1" 
        document-name="Scope and Membership Application" 
        :document-type="1" 
        :document-gets-reviewed="true"
        approve-button-label="Approve Scope and Membership"
    >
        <coi-log class="mb-6" :application="application">
        </coi-log>
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from './BaseStep'
import CoiLog from './COILog'

export default {
    name: 'StepOne',
    components: {
        BaseStep,
        CoiLog,
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