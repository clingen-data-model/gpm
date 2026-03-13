<script>
import CspecSummary from '@/components/expert_panels/CspecSummary.vue'
import ApplicationUploadForm from '@/components/applications/documents/ApplicationUploadForm.vue'
import { useAuthStore } from '@/stores/auth';

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
    setup() {
        return {
            authStore: useAuthStore(),
        }
    },
    data() {
        return {
        }
    },
    computed: {
        cspecSummaryEnabled () {
            return this.authStore.systemInfo.app.features.cspec_summary
        },
        specificationUpload () {
            return this.authStore.systemInfo.app.features.specification_upload
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
