<template>
    <base-step 
        :step="3"
        approve-button-label="Approve Pilot and Specifications"
    >
        <template v-slot:document>
            <div class="mb-4">
                <h3 class="text-lg font-bold mb-1">Final Specifications</h3>
                <document-manager 
                    :application="application"
                    :document-type-id="4"
                    :getsReviewd="false"
                ></document-manager>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-1">Pilot Classifications</h3>
                <document-manager 
                    :application="application"
                    :document-type-id="5"
                    :getsReviewd="false"
                ></document-manager>
            </div>
        </template>

        
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
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
            application: 'currentItem'
            })
    },
    methods: {

    }
}
</script>