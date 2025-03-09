<script>
import CspecSummary from '@/components/expert_panels/CspecSummary.vue'
import ApplicationUploadForm from '@/components/applications/documents/ApplicationUploadForm.vue'

export default {
    name: 'SpecificationsSection',
    components: {
        CspecSummary,
        ApplicationUploadForm
    },
    props: {
        docTypeId: {
            required: true,
            type: [Number, Array]
        },
        readonly: {
            type: Boolean,
            default: false
        }
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
<template>
    <div>
        <CspecSummary v-if="cspecSummaryEnabled" :readonly="readonly" />

        <collapsible class="mt-4">
            <template #title>
                <h3>Legacy document-based specifications</h3>
            </template>
            <ApplicationUploadForm
                v-if="specificationUpload"
                :document-type-id="docTypeId"
                :show-notes="false"
                :readonly="readonly"
            />
        </collapsible>
    </div>
</template>
