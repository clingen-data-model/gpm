<template>
    <base-step 
        :step="3"
        approve-button-label="Approve Pilot and Specifications"
    >
        <template v-slot:document>
            <h3 class="text-lg font-bold mb-1">Final Specifications</h3>
            <document-manager 
                :application="application"
                :document-type-id="4"
                :getsReviewd="false"
                :step="3"
                    class="mb-4"
            ></document-manager>

            <h3 class="text-lg font-bold mb-1">Pilot Classifications</h3>
            <document-manager 
                :application="application"
                :document-type-id="5"
                :getsReviewd="false"
                :step="3"
                    class="mb-6"
            ></document-manager>
        </template>        
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import {formatDate} from '../../date_utils'
import BaseStep from './BaseStep'
import DocumentManager from './DocumentManager'

export default {
    name: 'StepThree',
    components: {
        BaseStep,
        DocumentManager
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
        formatDate(dateString) {
            return formatDate(dateString)
        }
    }
}
</script>