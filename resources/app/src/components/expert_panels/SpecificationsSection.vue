<template>
    <div>
        <cspec-summary v-if="cspecSummaryEnabled" />
        <application-upload-form :document-type-id="docTypeId" :show-notes="false" v-if="specificationUpload" />
    </div>
</template>
<script>
import CspecSummary from '@/components/expert_panels/CspecSummary.vue'
import ApplicationUploadForm from '@/components/applications/documents/ApplicationUploadForm.vue'

export default {
    name: 'SpecificationsSection',
    props: {
        docTypeId: {
            required: true,
            type: [Number, Array]
        }
    },
    components: {
        CspecSummary,
        ApplicationUploadForm
    },
    data() {
        return {
            
        }
    },
    computed: {
        cspecSummaryEnabled () {
            return this.$store.state.systemInfo.app.features.cspec_summary
        },
        specificationUpload () {
            return this.$store.state.systemInfo.app.features.specification_upload
        }
    },
    methods: {

    }
}
</script>