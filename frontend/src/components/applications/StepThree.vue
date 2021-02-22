<template>
    <base-step 
        :step="3"
        approve-button-label="Approve Pilot and Specifications"
    >
        <template v-slot:document>
            <h3 class="text-lg font-bold mb-1">Final Specifications</h3>
            <div v-if="!application.stepIsApproved(3)">
                <div class="mb-4">
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
            </div>
            <div v-else
                class="pb-3 border-b mb-6"
            >
                <dictionary-row label="Final Specifications">
                    <a href="" class="text-blue-500 underline">{{application.finalDocumentOfType(3).filename}}</a>
                </dictionary-row>
                <dictionary-row label="Date Received">
                    {{formatDate(application.firstDocumentOfType(3).date_received)}}
                </dictionary-row>
                <dictionary-row label="Date Reviewed">
                    {{formatDate(application.finalDocumentOfType(3).date_reviewed)}}
                </dictionary-row>

                <br>
                <dictionary-row label="Final Pilot Classifications">
                    <a href="" class="text-blue-500 underline">{{application.finalDocumentOfType(4).filename}}</a>
                </dictionary-row>
                <dictionary-row label="Date Received">
                    {{formatDate(application.firstDocumentOfType(4).date_received)}}
                </dictionary-row>
                <dictionary-row label="Date Reviewed">
                    {{formatDate(application.finalDocumentOfType(4).date_reviewed)}}
                </dictionary-row>
                <br>
                <dictionary-row label="Date Approved">
                    {{formatDate(application.approvalDateForStep(3))}}
                </dictionary-row>
            </div>

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
            application: 'currentItem'
            })
    },
    methods: {
        formatDate(dateString) {
            return formatDate(dateString)
        }
    }
}
</script>