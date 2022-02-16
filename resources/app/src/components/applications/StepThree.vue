<template>
    <base-step 
        :step="3"
        approve-button-label="Approve Pilot and Specifications"
        title="Pilot ACMG Guideline Specificiations"
        @stepApproved="$emit('stepApproved')"
    >
        <template v-slot:document>
            <div>
                <div class="mt-4 p-4 border rounded-xl bg-gray-50">
                    <document-manager 
                        :application="application"
                        :document-type-id="3"
                        :getsReviewd="false"
                        :step="3"
                        class="mb-4"
                        title="Final Specifications"
                        @updated="$emit('updated')"
                    />
                </div>
                <div class="mt-4 p-4 border rounded-xl bg-gray-50">
                    <document-manager 
                        title="Pilot Classifications"
                        :application="application"
                        :document-type-id="4"
                        :getsReviewd="false"
                        :step="3"
                        @updated="$emit('updated')"
                    />
                </div>
                <div class="mt-4 p-4 border rounded-xl bg-gray-50">
                    <document-manager 
                        title="Additional Documents"
                        :application="application"
                        :document-type-id="7"
                        :getsReviewd="false"
                        :show-version="false"
                        :step="3"
                        @updated="$emit('updated')"
                    />
                </div>
            </div>
        </template>        
        <template v-slot:sections>
            <div class="application-section">
                <h2>Pilot Specifications</h2>
                <cspec-summary></cspec-summary>
            </div>
        </template>
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import {formatDate} from '@/date_utils'
import BaseStep from '@/components/applications/BaseStep'
import DocumentManager from '@/components/applications/documents/DocumentManager'
import CspecSummary from '@/components/expert_panels/CspecSummary'

export default {
    name: 'StepThree',
    components: {
        BaseStep,
        DocumentManager,
        CspecSummary
    },
    props: {
        
    },
    emits: ['stepApproved'],
    data() {
        return {
            
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
    },
    methods: {
        formatDate(dateString) {
            return formatDate(dateString)
        }
    }
}
</script>