<template>
    <base-step 
        :step="3"
        approve-button-label="Approve Pilot and Specifications"
        title="Pilot ACMG Guideline Specificiations"
    >
        <template v-slot:document>
            <document-manager 
                :application="application"
                :document-type-id="3"
                :getsReviewd="false"
                :step="3"
                class="mb-4"
                title="Final Specifications"
            ></document-manager>

            <document-manager 
                title="Pilot Classifications"
                :application="application"
                :document-type-id="4"
                :getsReviewd="false"
                :step="3"
                class="mb-6"
            ></document-manager>

            <document-manager 
                title="Additional Documents"
                :application="application"
                :document-type-id="7"
                :getsReviewd="false"
                :show-version="false"
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
import DocumentManager from './documents/DocumentManager'

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